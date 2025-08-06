@props(['items' => []])

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard') }}" class="text-decoration-none small">
                <i class="fas fa-home me-1"></i> 
                <span class="d-none d-sm-inline">Dashboard</span>
            </a>
        </li>
        
        @foreach($items as $item)
            @if($loop->last)
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">
                    @if(isset($item['icon']))
                        <i class="{{ $item['icon'] }} me-1"></i>
                    @endif
                    <span class="d-none d-sm-inline">{{ $item['label'] }}</span>
                    <span class="d-sm-none">{{ Str::limit($item['label'], 15) }}</span>
                </li>
            @else
                <li class="breadcrumb-item">
                    <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none small fw-medium">
                        @if(isset($item['icon']))
                            <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                        <span class="d-none d-sm-inline">{{ $item['label'] }}</span>
                        <span class="d-sm-none">{{ Str::limit($item['label'], 15) }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav> 