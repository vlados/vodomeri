<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-1 gap-4">
            <div class="text-center">
                <h2 class="text-xl font-semibold mb-4">Генериране на справки</h2>
                <p class="mb-6 text-gray-500">Използвайте бутона по-горе за да генерирате месечни справки за водомерите</p>
                
                <div class="flex justify-center">
                    <img src="{{ asset('images/dashboard.png') }}" alt="Reports" class="max-w-md rounded-lg shadow-lg">
                </div>
                
                <div class="mt-6 text-left">
                    <h3 class="text-lg font-medium mb-2">Налични справки:</h3>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Справка за гореща вода - обобщава показанията на водомерите за гореща вода, групирани по апартаменти</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>