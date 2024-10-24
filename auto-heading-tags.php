<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 1.5
Author: Alexis Olivero
Web: www.oliverodev.com
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class SimpleHeadingHierarchy {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter('the_content', array($this, 'process_headings'), 99);
    }
    
    public function process_headings($content) {
        if (empty($content)) {
            return $content;
        }

        // Crear un DOM del contenido
        $dom = new DOMDocument();
        
        // Preservar espacios en blanco
        $dom->preserveWhiteSpace = true;
        
        // Evitar errores con caracteres especiales
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        
        // Suprimir errores de warnings del DOM
        libxml_use_internal_errors(true);
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Obtener todos los encabezados
        $headings = array();
        for ($i = 1; $i <= 6; $i++) {
            $tags = $dom->getElementsByTagName('h' . $i);
            foreach ($tags as $tag) {
                $headings[] = $tag;
            }
        }

        if (empty($headings)) {
            return $content;
        }

        // Procesar los encabezados
        $current_level = 1;
        $found_h1 = false;

        foreach ($headings as $heading) {
            // Obtener el nivel actual del encabezado
            $current_tag = $heading->nodeName;
            $level = intval(substr($current_tag, 1));

            // Manejar el primer H1
            if ($level === 1 && !$found_h1) {
                $found_h1 = true;
                continue;
            }

            // Determinar el nuevo nivel
            if ($found_h1) {
                if ($level <= $current_level) {
                    $current_level++;
                }
                $new_level = min($current_level, 6);
            } else {
                $new_level = $level;
            }

            // Crear nuevo elemento
            $new_heading = $dom->createElement('h' . $new_level);
            
            // Copiar el contenido y atributos
            while ($heading->childNodes->length > 0) {
                $new_heading->appendChild($heading->childNodes->item(0));
            }
            foreach ($heading->attributes as $attribute) {
                $new_heading->setAttribute($attribute->name, $attribute->value);
            }

            // Reemplazar el encabezado original
            $heading->parentNode->replaceChild($new_heading, $heading);
        }

        // Obtener el HTML resultante
        $new_content = $dom->saveHTML();
        
        // Limpiar el HTML resultante
        $new_content = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $new_content));
        
        return trim($new_content);
    }
}

// Inicializar el plugin
function initialize_simple_heading_hierarchy() {
    SimpleHeadingHierarchy::get_instance();
}
add_action('plugins_loaded', 'initialize_simple_heading_hierarchy');

// Activación del plugin
register_activation_hook(__FILE__, function() {
    // Verificar versión mínima de PHP
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere PHP 7.0 o superior.');
    }
});
?>