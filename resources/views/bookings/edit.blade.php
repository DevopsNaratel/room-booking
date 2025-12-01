<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Booking') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('bookings.update', $booking) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-6">
                            <div>
                                <label for="room_id" class="block text-sm font-medium text-gray-700">Room</label>
                                <select id="room_id" name="room_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" @if($booking->room_id == $room->id) selected @endif>{{ $room->name }} (Capacity: {{ $room->capacity }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                <textarea id="purpose" name="purpose" rows="3" class="mt-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md" required>{{ old('purpose', $booking->purpose) }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                                    <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time', $booking->start_time->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                </div>

                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                                    <input type="datetime-local" id="end_time" name="end_time" value="{{ old('end_time', $booking->end_time->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('my-bookings.index') }}" class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 border border-gray-300 rounded-md shadow-sm">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
