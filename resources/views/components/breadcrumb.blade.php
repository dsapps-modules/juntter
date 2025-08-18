@props([
  'items' => [],
  'rightSub' => null,
  'filtroData' => null
])

<nav aria-label="breadcrumb"  class="mb-3 " style="background-color: #e9ecef;">
  <div class="d-flex align-items-center justify-content-between">
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
    
    <!-- Filtro de Data no meio -->
    @if($filtroData)
      <div class="d-flex align-items-center">
        <form id="filtroMesAnoForm" method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-1">
          <select name="mes" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
            <option value="">Todos</option>
            <option value="1" {{ $filtroData['mesAtual'] == 1 ? 'selected' : '' }}>Janeiro</option>
            <option value="2" {{ $filtroData['mesAtual'] == 2 ? 'selected' : '' }}>Fevereiro</option>
            <option value="3" {{ $filtroData['mesAtual'] == 3 ? 'selected' : '' }}>Março</option>
            <option value="4" {{ $filtroData['mesAtual'] == 4 ? 'selected' : '' }}>Abril</option>
            <option value="5" {{ $filtroData['mesAtual'] == 5 ? 'selected' : '' }}>Maio</option>
            <option value="6" {{ $filtroData['mesAtual'] == 6 ? 'selected' : '' }}>Junho</option>
            <option value="7" {{ $filtroData['mesAtual'] == 7 ? 'selected' : '' }}>Julho</option>
            <option value="8" {{ $filtroData['mesAtual'] == 8 ? 'selected' : '' }}>Agosto</option>
            <option value="9" {{ $filtroData['mesAtual'] == 9 ? 'selected' : '' }}>Setembro</option>
            <option value="10" {{ $filtroData['mesAtual'] == 10 ? 'selected' : '' }}>Outubro</option>
            <option value="11" {{ $filtroData['mesAtual'] == 11 ? 'selected' : '' }}>Novembro</option>
            <option value="12" {{ $filtroData['mesAtual'] == 12 ? 'selected' : '' }}>Dezembro</option>
          </select>
          <select name="ano" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
            <option value="">Todos</option>
            @for ($i = date('Y'); $i >= date('Y')-2; $i--)
              <option value="{{ $i }}" {{ $filtroData['anoAtual'] == $i ? 'selected' : '' }}>
                {{ $i }}
              </option>
            @endfor
          </select>
          <button type="submit" class="btn btn-warning btn-sm ml-2" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
            <i class="fas fa-filter"></i>
          </button>
        </form>
      </div>
    @endif
    
    <!-- RightSub no lado direito -->
    @if(!empty($rightSub))
      <div class="small text-muted d-none d-sm-block ms-3">
        {!! $rightSub !!}
      </div>
    @endif
  </div>
</nav>