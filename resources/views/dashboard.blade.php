<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Drivers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex space-x-2 mb-4">
                    <input type="text" id="search" placeholder="Поиск по имени или телефону"
                           class="p-2 border rounded w-full dark:bg-gray-700 dark:text-white">
                    <button id="search-btn" class="bg-blue-500 text-white px-4 py-2 rounded">
                        Поиск
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                        <thead>
                        <tr class="bg-gray-200 dark:bg-gray-700 text-left">
                            <th class="p-2 border">Имя</th>
                            <th class="p-2 border">Фамилия</th>
                            <th class="p-2 border">Машина</th>
                            <th class="p-2 border">Телефон</th>
                            <th class="p-2 border">Статус</th>
                            <th class="p-2 border">Действия</th>
                        </tr>
                        </thead>
                        <tbody id="drivers-table" class="dark:text-white">
                        </tbody>
                    </table>
                </div>

                <div id="pagination" class="mt-4 flex justify-center space-x-2">

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function renderPagination(currentPage, lastPage, search) {
        let pagination = $('#pagination').empty();
        if (lastPage <= 1) return;

        let pages = [];
        if (lastPage <= 7) {
            pages = Array.from({ length: lastPage }, (_, i) => i + 1);
        } else {
            pages = [1];

            if (currentPage > 4) {
                pages.push('...');
            }

            let start = Math.max(2, currentPage - 2);
            let end = Math.min(lastPage - 1, currentPage + 2);
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (currentPage < lastPage - 3) {
                pages.push('...');
            }

            pages.push(lastPage);
        }

        pages.forEach(page => {
            if (page === '...') {
                pagination.append(`<span class="px-2">...</span>`);
            } else {
                pagination.append(`
                <button class="pagination-btn px-3 py-1 border rounded ${page === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700'}"
                        onclick="loadDrivers(${page}, '${search}')">${page}</button>
            `);
            }
        });
    }

    function loadDrivers(page = 1, search = '') {
        $.ajax({
            url: '{{ route('drivers.index') }}',
            data: { page, search },
            success: function(response) {
                let tableBody = $('#drivers-table').empty();
                if (response.data.length === 0) {
                    tableBody.append(`<tr><td colspan="5" class="p-4 text-center">Нет данных</td></tr>`);
                } else {
                    response.data.forEach(driver => {
                        tableBody.append(`
                        <tr class="border-b">
                            <td class="p-2 border">${driver.name ?? '-'}</td>
                            <td class="p-2 border">${driver.surname ?? '-'}</td>
                            <td class="p-2 border">${formatCar(driver.car)}</td>
                            <td class="p-2 border">${driver.phone ?? '-'}</td>
                            <td class="p-2 border">${driver.active ?? '-'}</td>
                            <td class="p-2 border text-center">
                                <a href="${driver.lead_id ? 'https://tomxemmings.amocrm.ru/leads/detail/' + driver.lead_id : 'https://tomxemmings.amocrm.ru/leads'}"
                                   target="_blank"
                                   class="bg-green-500 text-white px-2 py-1 rounded">
                                    Перейти в amoCRM
                                </a>
                                <button class="delete-btn bg-red-500 text-white px-2 py-1 rounded"
                                        data-id="${driver.id}">
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    `);
                    });
                }

                renderPagination(response.current_page, response.last_page, search);
            }
        });
    }

    $(document).ready(() => loadDrivers());

    function formatCar(car) {
        if (!car) return '—';

        if (typeof car === 'string') {
            try {
                car = JSON.parse(car);
            } catch (e) {
                return car;
            }
        }

        return Object.entries(car)
            .map(([key, value]) => `${key}: ${value}`)
            .join('<br>');
    }

    $(document).on('click', '#search-btn', function() {
        let searchValue = $('#search').val().trim();
        loadDrivers(1, searchValue);
    });

    $(document).on('click', '.delete-btn', function() {
        let driverId = $(this).data('id');

        if (!confirm('Вы уверены, что хотите удалить водителя?')) return;

        $.ajax({
            url: `/drivers/${driverId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                alert(response.message);
                loadDrivers();
            },
            error: function(xhr) {
                alert('Ошибка при удалении водителя');
            }
        });
    });
</script>
