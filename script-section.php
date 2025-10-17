<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ScriptSectionPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        // Plugin nicht im Admin ausführen
        if ($this->isAdmin()) {
            return;
        }

        // Aktiviere Event zum Verarbeiten von Markdown-Rohinhalt
        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0],
        ]);
    }

    public function onPageContentRaw(Event $event): void
    {
        $page = $event['page'];
        $content = $page->getRawContent();

        $content = preg_replace_callback(
            '/\[!script-section\s+(.*?)\]/',
            function($matches) {
                $params = $matches[1];
                $name = basename(strtok($params, '?'));

                // Extract and pares query-part
                $queryString = parse_url($params, PHP_URL_QUERY);
                parse_str($queryString, $werte);

                // Fill variables (Standard: empty, if not available)
                $args = isset($werte['args']) ? explode(',', $werte['args']) : [];
                $classes = isset($werte['classes']) ? explode(',', $werte['classes']) : [];
                $title = isset($werte['title']) ? $werte['title'] : '';
                $icon = isset($werte['icon']) ? $werte['icon'] : '';
                $link = isset($werte['link']) ? $werte['link'] : '';

                //Script Run
                $scriptPath = ROOT_DIR . 'user/scripts/' . $name;
                if (file_exists($scriptPath)) {
                    // run skript as separate prozess via PHP CLI
                    $cmd = 'php ' . escapeshellarg($scriptPath) . ' ' . implode(' ', $args) . ' 2>&1';
                    $scriptout = shell_exec($cmd);
                    if ($scriptout === null) {
                        $scriptout = "Error: Execution failed for script '$scriptName'.";
                    }
                } else {
                    $scriptout = "Error: Script '$scriptName' not found.";
                }

                // Output
                $res  = '<div class="script-section ' . implode(' ', $classes) . '">';
                $res .= '<div class="title">';
                $res .= !empty($link) ? '<a href="' . $link . '" target="_blank" rel="noopener">' :'';
                if(!empty($icon)) {
                    $res .= '<i class="fa ' . $icon . '"></i> ';
                }
                $res .= $title;
                $res .= !empty($link) ? '</a>' : '';
                $res .= '</div>';
                $res .= $scriptout;

                //debug
                //$res .= '<hr>' . PHP_EOL;
                //$res .= 'Name: ' . $name . '<br>';
                //$res .= 'Icon: ' . $icon . '<br>';
                //$res .= 'Args: ' . implode(', ', $args) . '<br>';
                //$res .= 'Classes: ' . implode(', ', $classes) . '<hr><br>';

                $res .= '</div>' . PHP_EOL;
                return $res;
                },
            $content
        );

        $page->setRawContent($content);
    }
}
