@extends('layouts.auth')
@section('title', 'Unlock '.$category->name)
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Unlock {{ $category->name }}</h1>
    <p class="text-sm text-gray-400 mb-6">Choose a plan to start watching in full quality.</p>
@endsection
@section('content')
    @auth
        @if ($plans->isEmpty())
            <p class="text-sm text-gray-400 text-center py-6">No plans are available for this category yet.</p>
        @else
            <div class="space-y-3" x-data="{ plan: '{{ $plans->first()->id }}', gateway: '{{ array_key_first($gateways) ?? config('payments.default') }}' }">
                @foreach ($plans as $plan)
                    <label class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3 cursor-pointer"
                           :class="plan === '{{ $plan->id }}' ? 'ring-1 ring-violet-400' : ''">
                        <div>
                            <input type="radio" class="hidden" name="plan_pick" value="{{ $plan->id }}" x-model="plan">
                            <p class="text-sm font-medium text-gray-100">{{ $plan->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ ucfirst(str_replace('_', ' ', $plan->type)) }}
                                @if ($plan->validity_days) · {{ $plan->validity_days }} days @else · Lifetime @endif
                            </p>
                        </div>
                        <div class="text-right">
                            @if ($plan->compare_at_price)
                                <span class="text-xs text-gray-500 line-through">{{ money($plan->compare_at_price, $plan->currency) }}</span>
                            @endif
                            <p class="text-base font-semibold text-violet-300">{{ money($plan->price, $plan->currency) }}</p>
                        </div>
                    </label>
                @endforeach

                <form method="POST" action="{{ route('checkout.store') }}" class="pt-2">
                    @csrf
                    <input type="hidden" name="plan_id" :value="plan">
                    @if (count($gateways) > 1)
                        <label class="block text-sm mb-1.5 text-gray-300">Payment method</label>
                        <select name="gateway" x-model="gateway" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm mb-3">
                            @foreach ($gateways as $key => $gw)
                                <option value="{{ $key }}">{{ $gw->label() }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="gateway" value="{{ array_key_first($gateways) ?? config('payments.default') }}">
                    @endif
                    <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
                        Continue to payment
                    </button>
                </form>
            </div>
        @endif
    @else
        <p class="text-sm text-gray-400 mb-4">Please sign in to purchase access.</p>
        <a href="{{ route('login') }}" class="block text-center rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 font-medium text-white">Sign in</a>
    @endauth
@endsection
