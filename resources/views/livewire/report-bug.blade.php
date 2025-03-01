<div>
    <flux:navlist variant="outline">
        <flux:navlist.item icon="bug-ant" wire:click="openModal" >
        Докладвай проблем
        </flux:navlist.item>
    </flux:navlist>


    <!-- Модален прозорец за докладване на грешка -->
    <flux:modal title="Докладване на грешка" wire:model="isOpen">
        <div class="space-y-4">
            @if (session()->has('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <p class="text-gray-600 mb-4">
                Помогнете ни да подобрим системата, като докладвате всички грешки, които срещате. Всеки доклад ще бъде разгледан от нашия екип.
            </p>

            <form wire:submit="submit" class="space-y-4">
                <flux:input 
                    wire:model="title" 
                    label="Заглавие"
                    placeholder="Кратко описание на проблема"
                />

                <flux:textarea 
                    wire:model="description" 
                    label="Описание на проблема"
                    placeholder="Опишете подробно какво се случи"
                    rows="4"
                />

                <flux:textarea 
                    wire:model="stepsToReproduce" 
                    label="Стъпки за възпроизвеждане (по избор)"
                    placeholder="Опишете стъпките, необходими за възпроизвеждане на проблема"
                    rows="3"
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Екранна снимка (по избор)</label>
                    <input type="file" wire:model="screenshot" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-gray-50 file:text-blue-600
                        hover:file:bg-blue-50
                    "/>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button  wire:click="closeModal" type="button">
                        Отказ
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <div wire:loading wire:target="submit" class="animate-spin mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <span>Изпрати</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
