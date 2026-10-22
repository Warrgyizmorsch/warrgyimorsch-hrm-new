@php $c = \App\Models\Asset::STATUS_COLORS[$status] ?? 'secondary'; @endphp
<span class="badge am-badge bg-soft-{{ $c }} text-{{ $c }}">{{ $status }}</span>
