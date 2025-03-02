@if(auth()->check() && auth()->user()->isImpersonated())
<div class="pointer-events-none fixed inset-x-0 bottom-0 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8">
    <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-red-600 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pr-3.5 sm:pl-4">
        <p class="text-sm/6 text-white">
            Вие сте влезли като <strong>{{ auth()->user()->name }}</strong>
        </p>
        <a href="/impersonate/leave" class="px-3 py-1 text-white bg-red-800 rounded-md hover:bg-red-700">
            Изход
        </a>
    </div>
</div>

@endif