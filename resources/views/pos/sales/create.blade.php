
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-xl font-semibold mb-4">Create New Sale</h2>
                    
                    <p class="mb-4">You are being redirected to the POS Dashboard to create a new sale.</p>
                    
                    <div class="flex items-center">
                        <a href="{{ route('pos.dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Go to POS Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto redirect to dashboard after 2 seconds
        setTimeout(function() {
            window.location.href = "{{ route('pos.dashboard') }}";
        }, 2000);
    </script>
</x-app-layout>