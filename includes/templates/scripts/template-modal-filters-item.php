<?php
/**
 * Template Library Filter Item.
 */
?>
<label class="emshelementor-template-filter-label">
    <input type="radio" value="{{ slug }}" <# if ( '' === slug ) { #> checked<# } #> name="emshelementor-template-filter">
    <span>{{ title.replace('&amp;', '&') }}</span>
</label>