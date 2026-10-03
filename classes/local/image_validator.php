<?php
namespace local_activityicons\local;

/** Conservative checks for small decorative icon uploads, also applied on restore. */
final class image_validator {
    public const MAX_BYTES = 1048576;

    public static function sanitise(string $filename, string $content): array {
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            return ['content' => self::svg($content), 'mimetype' => 'image/svg+xml', 'extension' => 'svg'];
        }
        $mime = ['png' => 'image/png', 'webp' => 'image/webp'][$extension] ?? '';
        $size = @getimagesizefromstring($content);
        if (!$mime || !$size || ($size['mime'] ?? '') !== $mime || $size[0] < 1 || $size[1] < 1
                || $size[0] > 2048 || $size[1] > 2048) {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        // Decode and re-encode so metadata or trailing payloads cannot become part of a served asset.
        $image = @imagecreatefromstring($content);
        if (!$image) {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        return ['content' => $bytes, 'mimetype' => 'image/png', 'extension' => 'png'];
    }

    private static function svg(string $content): string {
        // Remove only the conventional SVG 1.1 declaration, before XML parsing. Never load its DTD.
        $content = preg_replace('~<!DOCTYPE\s+svg\s+PUBLIC\s+([\'"])-//W3C//DTD SVG 1\.1//EN\1\s+'
            . '([\'"])https?://www\.w3\.org/Graphics/SVG/1\.1/DTD/svg11\.dtd\2\s*>~i', '', $content);
        if (preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $content)) {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->resolveExternals = false;
        $doc->substituteEntities = false;
        try {
            $loaded = $doc->loadXML($content, LIBXML_NONET | LIBXML_NOBLANKS);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $root = $doc->documentElement;
        if (!$loaded || $doc->doctype || !$root || $root->localName !== 'svg' || !$root->hasAttribute('viewBox')) {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        self::clean_node($root);
        $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        return $doc->saveXML($root);
    }

    private static function clean_node(\DOMElement $node): void {
        $elements = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
            'title', 'desc', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath'];
        $attributes = ['xmlns', 'viewBox', 'preserveAspectRatio', 'fill', 'fill-opacity', 'fill-rule',
            'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-opacity', 'stroke-dasharray',
            'stroke-miterlimit', 'stroke-dashoffset', 'clip-rule', 'color',
            'opacity', 'transform', 'd', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
            'width', 'height', 'points', 'offset', 'stop-color', 'stop-opacity', 'gradientUnits',
            'gradientTransform', 'spreadMethod', 'id', 'clip-path'];
        if (!in_array($node->localName, $elements, true)
                || $node->namespaceURI !== 'http://www.w3.org/2000/svg') {
            throw new \invalid_parameter_exception(get_string('invalidimage', 'local_activityicons'));
        }
        // Exporters commonly put ordinary presentation attributes in style. Keep this safe subset, not raw CSS.
        $presentation = ['fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap',
            'stroke-linejoin', 'stroke-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit',
            'clip-rule', 'opacity', 'color', 'stop-color', 'stop-opacity'];
        foreach (explode(';', $node->getAttribute('style')) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2 && in_array(strtolower(trim($parts[0])), $presentation, true)) {
                $node->setAttribute(strtolower(trim($parts[0])), trim($parts[1]));
            }
        }
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $value = trim($attribute->value);
            if (!in_array($attribute->name, $attributes, true)
                    || preg_match('/(?:javascript:|data:|https?:|@import|expression\s*\(|[\\\\])/i', $value)
                    || (stripos($value, 'url') !== false &&
                        !preg_match('/^url\(#[a-zA-Z_][a-zA-Z0-9_.:-]*\)$/', $value))) {
                $node->removeAttributeNode($attribute);
            }
        }
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                self::clean_node($child);
            } else if (!($child instanceof \DOMText)) {
                $node->removeChild($child);
            }
        }
    }
}
