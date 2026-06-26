
<?php
if (! function_exists('jsToken')) {
    function jsToken(): string {
        return csrf_hash();
    }
}
