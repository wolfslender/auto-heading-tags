<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos dentro de una publicación según su jerarquía.
Version: 1.2
Author: Alexis Olivero
web: www.oliverodev.com
*/

if (!defined('ABSPATH')) exit; // Evita el acceso directo

// Función para aplicar automáticamente las etiquetas H1, H2, H3, etc., en los títulos de una publicación
function auto_heading_tags_by_hierarchy($content) {
    // Expresión regular para detectar todos los títulos que se encuentren en párrafos (<p>)
    $pattern = '/<p>(.*?<\/?p>)/i'; // Detecta cualquier título dentro de un párrafo <p>...</p>
    preg_match_all($pattern, $content, $matches);
    
    $current_heading_level = 1; // Inicia desde H1

    if (!empty($matches[1])) {
        foreach ($matches[1] as $key => $title) {
            // Si ya se ha aplicado un H1, aumentamos el nivel de encabezado (H2, H3, etc.)
            if ($current_heading_level > 6) {
                $current_heading_level = 6; // Limita el máximo a H6
            }

            // Reemplaza el título original con su versión etiquetada con H1, H2, H3, etc.
            $replace = '<h' . $current_heading_level . '>' . $title . '</h' . $current_heading_level . '>';
            $content = str_replace($matches[0][$key], $replace, $content);

            // Aumenta el nivel de encabezado para el siguiente título
            $current_heading_level++;
        }
    }

    return $content;
}

// Aplicar el filtro al contenido de las publicaciones
add_filter('the_content', 'auto_heading_tags_by_hierarchy');
