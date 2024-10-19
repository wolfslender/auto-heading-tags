<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos dentro de una publicación según su jerarquía. Convierte automáticamente los títulos repetidos en el siguiente nivel jerárquico.
Version: 1.3
Author: Alexis Olivero
web: www.oliverodev.com
*/

if (!defined('ABSPATH')) exit; // Evita el acceso directo

// Función para aplicar jerarquía correcta a los encabezados H1, H2, H3, etc.
function auto_heading_tags_by_hierarchy($content) {
    // Expresión regular para detectar todos los encabezados <h1> a <h6>
    $pattern = '/<h([1-6])>(.*?)<\/h\1>/i'; // Detecta todos los encabezados <h1>, <h2>, ..., <h6>
    preg_match_all($pattern, $content, $matches);
    
    $current_heading_level = 1; // Inicia desde H1 para el primer encabezado
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $key => $original_tag) {
            // Si ya hay un <h1>, los siguientes <h1> se convierten en <h2>, y así sucesivamente
            if ($current_heading_level > 6) {
                $current_heading_level = 6; // Limita el máximo a H6
            }

            // Reemplaza el encabezado original con el nuevo nivel jerárquico
            $new_tag = '<h' . $current_heading_level . '>' . $matches[2][$key] . '</h' . $current_heading_level . '>';
            $content = str_replace($original_tag, $new_tag, $content);

            // Aumenta el nivel de encabezado para el siguiente título
            $current_heading_level++;
        }
    }

    return $content;
}

// Aplicar el filtro al contenido de las publicaciones
add_filter('the_content', 'auto_heading_tags_by_hierarchy');
