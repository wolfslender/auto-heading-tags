<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2 y H3 a los títulos dentro de una publicación según su jerarquía numérica.
Version: 1.2
Author: Alexis Olivero
web: www.oliverodev.com
*/

if (!defined('ABSPATH')) exit; // Evita el acceso directo

// Función para aplicar automáticamente las etiquetas H1, H2 y H3 solo a títulos que tengan jerarquía
function auto_heading_tags_by_hierarchy($content) {
    // Expresión regular para detectar títulos con jerarquía numérica (por ejemplo, "1. Título" o "1.1 Subtítulo")
    $pattern = '/<p>(\d+(\.\d+)*\s+.*?)<\/p>/'; 
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $key => $title) {
            // Calcula el nivel de encabezado (H1, H2, H3) basándote en el número de puntos en la jerarquía (por ejemplo, 1. es H1, 1.1 es H2, 1.1.1 es H3)
            $hierarchy_level = substr_count($title, '.') + 1;

            if ($hierarchy_level > 3) {
                $hierarchy_level = 3; // Limita el máximo a H3
            }

            // Reemplaza el título original con su versión etiquetada con H1, H2 o H3
            $replace = '<h' . $hierarchy_level . '>' . $title . '</h' . $hierarchy_level . '>';
            $content = str_replace($matches[0][$key], $replace, $content);
        }
    }

    return $content;
}

// Aplicar el filtro al contenido de las publicaciones
add_filter('the_content', 'auto_heading_tags_by_hierarchy');
