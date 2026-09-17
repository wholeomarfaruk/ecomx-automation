@props([
    'wireModel',
    'menubar' => true,
    // Full toolbar — every command the package ships, grouped to match its
    // own plugin set (undo/redo, block format, fonts, inline formatting,
    // colors, alignment, lists, indent, quote/hr, links, media, table, code
    // sample, charmap/emoticons, search & replace, visual blocks, spacing).
    'toolbar' => 'undo redo | blockformat | fontfamily fontsize | bold italic underline strikethrough subscript superscript | forecolor backcolor removeformat | align alignleft aligncenter alignright alignjustify | lineheight | bullist numlist indent outdent | blockquote hr | link unlink anchor | image media embed | table | codesample | charmap emoticons | searchreplace | visualblocks | spacing',
    // Every plugin the package registers (see dist/index.d.ts side-effect
    // imports) — link/anchor are separate plugin ids from the "links"
    // bundle, autolink has no toolbar button but is enabled implicitly by
    // "link"; the rest map 1:1 to a toolbar group above.
    'plugins' => ['link', 'anchor', 'image', 'media', 'embed', 'table', 'codesample', 'emoticons', 'charmap', 'searchreplace', 'wordcount', 'elementpath', 'spacing', 'visualblocks'],
])

{{--
    omar-text-editor (https://github.com/wholeomarfaruk/rich-text-editor) wired
    into Livewire. wire:ignore keeps Livewire's re-renders from touching the
    editor's own DOM (toolbar + contentEditable div); the hidden textarea
    stays inside that ignored subtree so wire:model keeps working — the
    editor dispatches native input/change events on it as content changes.
    Prefer wire:model.blur (or a debounce) on the underlying property rather
    than plain wire:model, so typing doesn't fire a request per keystroke.
    Defaults here are the package's full feature set; pass toolbar/plugins/
    menubar explicitly on a given usage to trim it down instead.
--}}
<div {{ $attributes->merge(['class' => '']) }} wire:ignore x-data="omarTextEditor({
        toolbar: @js($toolbar),
        plugins: @js($plugins),
        menubar: @js($menubar),
    })" x-init="init($el)">
    <textarea wire:model="{{ $wireModel }}" style="display:none"></textarea>
</div>
