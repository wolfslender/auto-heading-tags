<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos dentro de una publicación según su jerarquía. Convierte automáticamente los títulos repetidos en el siguiente nivel jerárquico.
Version: 1.4
Author: Alexis Olivero
web: www.oliverodev.com
*/

if (!defined('ABSPATH')) exit; // Evita el acceso directo

// Función para aplicar jerarquía correcta a los encabezados H1, H2, H3, etc.
function auto_heading_tags_by_hierarchy($content) {
    // Expresión regular para detectar todos los encabezados <h1> a <h6>
    $pattern = '/<h([1-6])>(.*?)<\/h\1>/i'; // Detecta todos los encabezados <h1>, <h2>, ..., <h6>
    preg_match_all($pattern, $content, $matches);
    
    $has_h1 = false; // Variable para rastrear si ya existe un <h1>
    $current_heading_level = 1; // Inicia desde H1 para el primer encabezado
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $key => $original_tag) {
            $original_level = (int) $matches[1][$key]; // Captura el nivel del encabezado original
            
            // Verifica si ya existe un <h1> en el contenido
            if ($original_level == 1 && !$has_h1) {
                $has_h1 = true; // Marca que el <h1> ya existe
                $new_tag = $original_tag; // Deja el primer <h1> sin cambios
            } else {
                // Si el original es <h1> y ya existe uno, o es otro nivel
                if ($original_level <= $current_heading_level) {
                    $current_heading_level++; // Aumenta el nivel para mantener la jerarquía
                }
                
                // Asegura que el nivel no sobrepase <h6>
                if ($current_heading_level > 6) {
                    $current_heading_level = 6;
                }
                
                // Reemplaza el encabezado con el nuevo nivel
                $new_tag = '<h' . $current_heading_level . '>' . $matches[2][$key] . '</h' . $current_heading_level . '>';
            }
            
            // Reemplaza el encabezado original con el nuevo
            $content = str_replace($original_tag, $new_tag, $content);
        }
    }

    return $content;
}

// Aplicar el filtro al contenido de las publicaciones
add_filter('the_content', 'auto_heading_tags_by_hierarchy');

?>
