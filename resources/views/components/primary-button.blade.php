<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all ease-in-out duration-200 shadow-sm hover:shadow-md']) }}>
    {{ $slot }}
</button>
