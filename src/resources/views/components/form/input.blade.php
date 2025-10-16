@props([
    "name",
    "label",
    "type" => "text",
    "value" => null,
    "autocomplete" => null,
])

@php
    $isPassword = $type === "password";
    $isTextarea = $type === "textarea";
    // パスワードはautocomplete OFF
    $auto = $isPassword ? "off" : $autocomplete;
    $id = $attributes->get("id") ?? $name;
    $errorId = $id . "-error";
    $oldValue = old($name, $value);
@endphp

<label class="form-group">
    <span class="form-group__label">{{ $label }}</span>
    @if ($isTextarea)
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            class="form-group__control @error($name) is-invalid @enderror"
            @if ($auto)
                autocomplete="{{ $auto }}"
            @endif
            @error($name)
                aria-invalid="true"
                aria-describedby="{{ $errorId }}"
            @enderror
            {{ $attributes }}
        >
{{ $oldValue }}</textarea
        >
    @else
        <input
            class="form-group__control @error($name) is-invalid @enderror"
            id="{{ $id }}"
            type="{{ $type }}"
            name="{{ $name }}"
            @unless ($isPassword)
                value="{{ old($name, $value) }}"
            @endunless
            @if ($auto)
                autocomplete="{{ $auto }}"
            @endif
            {{ $attributes }}
        />
    @endif
    @error($name)
        <span id="{{ $errorId }}" class="form-error">{{ $message }}</span>
    @enderror
</label>
