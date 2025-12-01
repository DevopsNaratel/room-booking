<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Bookings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Search and Filter Form -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                        <form action="{{ route('admin.bookings.index') }}" method="GET">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 items-end">
                                <!-- User Search -->
                                <div>
                                    <label for="user" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                                    <input type="text" name="user" id="user" class="form-input block w-full text-sm rounded-md border-gray-300 shadow-sm" placeholder="Search by user name..." value="{{ request('user') }}">
                                </div>
                                <!-- Room Search -->
                                <div>
                                    <label for="room" class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                                    <input type="text" name="room" id="room" class="form-input block w-full text-sm rounded-md border-gray-300 shadow-sm" placeholder="Search by room name..." value="{{ request('room') }}">
                                </div>
                                <!-- Status Filter -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="form-select block w-full text-sm rounded-md border-gray-300 shadow-sm">
                                        <option value="">All Statuses</option>
                                        <option value="pending" @if(request('status') == 'pending') selected @endif>Pending</option>
                                        <option value="approved" @if(request('status') == 'approved') selected @endif>Approved</option>
                                        <option value="rejected" @if(request('status') == 'rejected') selected @endif>Rejected</option>
                                    </select>
                                </div>
                                <!-- Date Filter -->
                                <div>
                                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                    <input type="date" name="date" id="date" class="form-input block w-full text-sm rounded-md border-gray-300 shadow-sm" value="{{ request('date') }}">
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex space-x-2">
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Filter
                                    </button>
                                    <a href="{{ route('admin.bookings.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <h3 class="text-lg font-semibold mb-4">All Booking Requests</h3>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Room
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Purpose
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($bookings as $booking)
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    {{-- User --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold uppercase text-xs mr-3">
                                                {{ substr($booking->user->name, 0, 2) }}
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">{{ $booking->user->name }}</div>
                                        </div>
                                    </td>

                                    {{-- Room --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ $booking->room->name }}</div>
                                    </td>

                                    {{-- Time --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $booking->start_time->format('M d, Y') }}</div>
                                            <div class="text-gray-500 text-xs">
                                                {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Purpose --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $booking->purpose }}">
                                            {{ $booking->purpose }}
                                        </div>
                                    </td>

                                    {{-- Status (Modern Pill Design) --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusClasses = match($booking->status) {
                                                'approved' => 'bg-green-100 text-green-800 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                                default    => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                            };
                                            $dotColor = match($booking->status) {
                                                'approved' => 'bg-green-500',
                                                'rejected' => 'bg-red-500',
                                                default    => 'bg-yellow-500',
                                            };
                                        @endphp
                                        <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full border {{ $statusClasses }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-2 {{ $dotColor }}"></span>
                                            {{ ucfirst($booking->status) }}
                                        </span>
                                    </td>

                                    {{-- Actions (Icon Buttons) --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            {{-- Approve Button --}}
                                            @if ($booking->status !== 'approved')
                                                <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="group flex items-center justify-center w-8 h-8 rounded-full bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition duration-200" title="Approve">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Reject Button --}}
                                            @if ($booking->status !== 'rejected')
                                                <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="group flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition duration-200" title="Reject">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            {{-- If approved, show checkmark visual only (Optional) --}}
                                            @if($booking->status === 'approved')
                                                <span class="text-gray-300 pointer-events-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
                                        <p class="mt-1 text-sm text-gray-500">New booking requests will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>