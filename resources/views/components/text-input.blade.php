@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-rose-400 focus:ring-rose-400/50 rounded-xl shadow-sm transition-all duration-300 ease-in-out ' . ($disabled ? 'bg-gray-100/50 cursor-not-allowed text-gray-500' : '')]) }}>
