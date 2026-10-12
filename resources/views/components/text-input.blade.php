@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-slate-300 bg-white text-slate-900 placeholder-slate-400 rounded-md shadow-sm focus:border-blue-600 focus:ring-blue-600']) }}>