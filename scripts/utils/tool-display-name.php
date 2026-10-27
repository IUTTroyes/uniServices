<?php

/** Remove the technical bundle suffix from a user-facing application name. */
function tool_display_name(string $name, string $fallback = 'Application'): string
{
    $label = trim(preg_replace('/[\s_-]*bundle$/i', '', trim($name)));
    return $label !== '' ? $label : $fallback;
}
