@extends('layouts.admin')

@section('title', __('admin.recaptcha_blocked_ips'))

@section('content')
    <h1 class="text-2xl font-bold mb-4">{{ __('admin.recaptcha_blocked_ips') }}</h1>

    {{-- Eliminar el div de session('status') de aquí, ya lo maneja <x-alerts/> --}}

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">{{ __('admin.ip_address') }}</th>
                    <th class="py-3 px-6 text-left">{{ __('admin.attempts') }}</th>
                    <th class="py-3 px-6 text-left">{{ __('admin.last_attempt') }}</th>
                    <th class="py-3 px-6 text-left">{{ __('admin.blocked') }}</th>
                    <th class="py-3 px-6 text-left">{{ __('admin.time_remaining') }}</th>
                    <th class="py-3 px-6 text-center">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-light">
                @foreach($blockedIps as $ip)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap">{{ $ip->ip }}</td>
                        <td class="py-3 px-6 text-left">{{ $ip->attempts }}</td>
                        <td class="py-3 px-6 text-left">{{ $ip->last_attempt_at->format('Y-m-d H:i') }}</td>
                        <td class="py-3 px-6 text-left">
                            @if($ip->blocked_at)
                                <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                    <span aria-hidden class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                    <span class="relative">{{ __('admin.yes') }}</span>
                                </span>
                            @else
                                <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                    <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                    <span class="relative">{{ __('admin.no') }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-left">
                            @if($ip->isBlocked())
                                {{ $ip->remainingBlockMinutes() }} {{ __('admin.minutes_short') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            <form action="{{ route('admin.recaptcha.destroy', $ip->ip) }}" method="POST" onsubmit="return confirm('{{ __('admin.confirm_unblock_ip', ['ip' => $ip->ip]) }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    {{ __('admin.unblock') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $blockedIps->links() }}
    </div>
@endsection