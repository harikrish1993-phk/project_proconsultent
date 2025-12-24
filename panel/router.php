<?php
class Router {
    public static function moduleUrl($module, $page = 'index') {
        return '/panel/modules/' . $module . '/' . $page . '.php';
    }
    
    public static function assetUrl($path) {
        return '/panel/assets/' . ltrim($path, '/');
    }
}