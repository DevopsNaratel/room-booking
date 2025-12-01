<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Admin Dashboard') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage rooms and bookings</p>
            </div>
            <div class="text-sm text-gray-500">
                {{ now()->format('F j, Y') }}
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Rooms</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $totalRooms ?? '0' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Approved Bookings</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $approvedBookings ?? '0' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending Requests</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $pendingBookings ?? '0' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <a href="{{ route('admin.rooms.index') }}" class="group bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md hover:border-indigo-200 transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-lg font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">Manage Rooms</h4>
                            <p class="text-gray-600 mt-1">Add, edit, or delete meeting rooms</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('admin.bookings.index') }}" class="group bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md hover:border-indigo-200 transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-lg font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">Manage Bookings</h4>
                            <p class="text-gray-600 mt-1">Approve or reject booking requests</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Calendar Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Bookings Calendar</h3>
                            <p class="text-sm text-gray-500 mt-1">Overview of room bookings for {{ $date->format('F Y') }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.dashboard', ['year' => $previousMonthDate->year, 'month' => $previousMonthDate->month]) }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Previous
                            </a>
                            <a href="{{ route('admin.dashboard', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                Next
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="grid grid-cols-7 gap-2 text-center text-sm font-semibold text-gray-600 mb-3">
                            <div class="p-2">Mon</div>
                            <div class="p-2">Tue</div>
                            <div class="p-2">Wed</div>
                            <div class="p-2">Thu</div>
                            <div class="p-2">Fri</div>
                            <div class="p-2">Sat</div>
                            <div class="p-2">Sun</div>
                        </div>

                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($calendar as $day)
                                <div class="relative min-h-[80px] border border-gray-200 rounded-lg flex flex-col items-center justify-start p-2
                                            @if ($day === null) bg-gray-100
                                            @else bg-white @endif">
                                    @if ($day !== null)
                                        <span class="text-sm font-medium text-gray-900 mb-1">{{ $day }}</span>
                                        @if (isset($bookedDays[$day]))
                                            <div class="flex-grow w-full space-y-1 overflow-y-auto max-h-12">
                                                @foreach ($bookedDays[$day] as $booking)
                                                    <div class="bg-indigo-100 text-indigo-800 rounded px-2 py-1 text-xs truncate"
                                                         title="{{ $booking->room->name }} ({{ $booking->user->name }}) {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}">
                                                        {{ $booking->room->name }} {{ $booking->start_time->format('H:i') }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Calendar Legend -->
                    <div class="mt-4 flex items-center justify-center space-x-4 text-xs text-gray-500">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-indigo-100 rounded mr-1"></div>
                            <span>Booked</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-gray-100 rounded mr-1"></div>
                            <span>Empty day</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>