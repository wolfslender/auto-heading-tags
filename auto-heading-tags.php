<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 1.5
Author: Alexis Olivero
Web: www.oliverodev.com
*/

if (!defined('ABSPATH')) exit;

// Añadir opciones de debug
function auto_heading_tags_debug_log($message) {
    if (WP_DEBUG === true) {
        error_log('[Auto Heading Tags] ' . $message);
    }
}

function auto_heading_tags_by_hierarchy($content) {
    try {
        // Verificar si el contenido está vacío
        if (empty($content)) {
            auto_heading_tags_debug_log('Contenido vacío');
            return $content;
        }

        // Patrón mejorado que considera atributos HTML
        $pattern = '/<h([1-6])(.*?)>(.*?)<\/h\1>/i';
        
        if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            auto_heading_tags_debug_log('No se encontraron encabezados');
            return $content;
        }

        $has_h1 = false;
        $current_heading_level = 1;
        $processed_content = $content;
        
        // Array para almacenar los reemplazos
        $replacements = [];

        foreach ($matches as $match) {
            $original_tag = $match[0];
            $original_level = (int) $match[1];
            $attributes = $match[2]; // Preservar atributos HTML
            $heading_content = $match[3];

            if ($original_level === 1 && !$has_h1) {
                $has_h1 = true;
                continue; // Mantener el primer H1 sin cambios
            }

            // Determinar nuevo nivel
            if ($original_level <= $current_heading_level) {
                $current_heading_level++;
            }

            // Limitar a H6
            $new_level = min($current_heading_level, 6);

            // Crear nuevo tag preservando atributos
            $new_tag = "<h{$new_level}{$attributes}>{$heading_content}</h{$new_level}>";
            
            // Almacenar el reemplazo
            $replacements[$original_tag] = $new_tag;
        }

        // Realizar todos los reemplazos de una vez
        $processed_content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $processed_content
        );

        auto_heading_tags_debug_log('Procesamiento completado exitosamente');
        return $processed_content;

    } catch (Exception $e) {
        auto_heading_tags_debug_log('Error: ' . $e->getMessage());
        return $content; // Devolver contenido original en caso de error
    }
}

// Añadir el filtro con prioridad específica
add_filter('the_content', 'auto_heading_tags_by_hierarchy', 20);

// Función de activación del plugin
register_activation_hook(__FILE__, function() {
    auto_heading_tags_debug_log('Plugin activado');
});

// Función de desactivación del plugin
register_deactivation_hook(__FILE__, function() {
    auto_heading_tags_debug_log('Plugin desactivado');
});
?>