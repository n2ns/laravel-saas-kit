{{--
    Responsive image component with optional webp source.

    Usage:
      <x-products.responsive-image src="..." alt="..." img-class="..." />

    Props:
      - src: 图片路径（相对 public 目录）
      - alt: 图片 alt 文案
      - imgClass: img 的 class
      - pictureClass: picture 标签 class
      - loading: img loading 属性（如 lazy）
      - decoding: img decoding 属性（如 async）
--}}

@props([
    'src' => '',
    'alt' => '',
    'imgClass' => '',
    'pictureClass' => '',
    'loading' => null,
    'decoding' => null,
])

@php
    $hasSource = (bool) $src;
    $basePath = null;
    $hasPictureClass = trim((string) $pictureClass) !== '';

    if ($hasSource) {
        $pathInfo = pathinfo($src);
        $dirname = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';

        if ($dirname !== '' && $filename !== '') {
            $basePath = $dirname . '/' . $filename;
        }
    }
@endphp

<picture{!! $hasPictureClass ? ' class="'.$pictureClass.'"' : '' !!}>
    @if($hasSource && $basePath && file_exists(public_path($basePath . '.webp')))
        <source srcset="{{ asset($basePath . '.webp') }}" type="image/webp">
    @endif
    @if($hasSource)
        <img
            src="{{ asset($src) }}"
            alt="{{ $alt }}"
            class="{{ $imgClass }}"
            @if($loading) loading="{{ $loading }}" @endif
            @if($decoding) decoding="{{ $decoding }}" @endif
        >
    @endif
</picture>
