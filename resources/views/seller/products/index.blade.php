<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Produtos</title></head>
<body>
    <h1>Produtos</h1>
    <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 16px;">
        @foreach($products as $product)
            <li style="display: flex; gap: 12px; align-items: center; min-width: 0;">
                <div style="width: 56px; height: 56px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #f8fafc; display: flex; align-items: center; justify-content: center; flex: 0 0 56px; line-height: 0;">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="Miniatura de {{ $product->name }}" style="display: block; width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: cover;">
                    @else
                        <span style="font-size: 12px; color: #6b7280;">Sem imagem</span>
                    @endif
                </div>
                <div style="min-width: 0; flex: 1 1 auto; overflow: hidden;">
                    <div style="font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->name }}</div>
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;">{{ $product->short_description }}</div>
                    <div>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</div>
                </div>
            </li>
        @endforeach
    </ul>
</body>
</html>
