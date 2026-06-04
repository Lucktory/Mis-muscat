<div>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

        @if ($open)
        <div class="flex h-[32rem] w-[22rem] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-slate-200 bg-[#0f3b66] px-4 py-3">
                <div>
                    <div class="text-sm font-semibold text-white">MIS Logistics Tracking</div>
                    <div class="text-xs text-blue-100">
                        <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-blue-300 align-middle"></span>
                        Self-service, instant reply
                    </div>
                </div>
                <button type="button" wire:click="toggle"
                        class="rounded p-1 text-blue-100 hover:bg-white/10 hover:text-white"
                        aria-label="Close chat">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="chat-messages"
                 class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-3 py-4"
                 x-data="{ scrollDown() { this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight }) } }"
                 x-init="scrollDown()"
                 @scroll-chat-bottom.window="scrollDown()">
                @foreach ($messages as $msg)
                    @if ($msg['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] rounded-lg rounded-br-sm bg-[#0f3b66] px-3 py-2 text-sm text-white">
                                <div class="whitespace-pre-wrap">{{ $msg['body'] }}</div>
                                <div class="mt-1 text-right text-[10px] text-blue-200">{{ $msg['ts'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[85%] rounded-lg rounded-bl-sm border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800">
                                <div class="whitespace-pre-wrap font-mono text-[12.5px] leading-relaxed">{{ $msg['body'] }}</div>
                                <div class="mt-1 text-[10px] text-slate-400">MIS · {{ $msg['ts'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <form wire:submit.prevent="send" class="border-t border-slate-200 bg-white p-3">
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="input"
                           placeholder="Booking, container, or HELP"
                           autofocus
                           class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm placeholder-slate-400 focus:border-[#0f3b66] focus:outline-none focus:ring-1 focus:ring-[#0f3b66]">
                    <button type="submit"
                            class="rounded-md bg-[#0f3b66] px-3 py-2 text-sm font-medium text-white hover:bg-[#0a2a4d]">
                        Send
                    </button>
                </div>
                <div class="mt-2 text-[11px] text-slate-500">
                    Every reply is timestamped and logged. Nothing taken on faith. Everything on the track.
                </div>
            </form>
        </div>
        @endif

        <button type="button" wire:click="toggle"
                class="flex items-center gap-2 rounded-full bg-[#0f3b66] px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-[#0a2a4d]">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-300"></span>
            </span>
            <span>{{ $open ? 'Tracking assistant' : 'Track your shipment' }}</span>
        </button>
    </div>
</div>
