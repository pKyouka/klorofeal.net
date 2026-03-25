import './bootstrap';
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const currencyFormatter = new Intl.NumberFormat('id-ID');

const debounce = (callback, wait = 300) => {
	let timeoutId;

	return (...args) => {
		window.clearTimeout(timeoutId);
		timeoutId = window.setTimeout(() => callback(...args), wait);
	};
};

const renderProductActions = (row, csrfToken) => `
	<div class="flex items-center justify-end gap-2">
		<a href="${row.edit_url}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
		<form method="POST" action="${row.delete_url}" onsubmit="return confirm('Delete this product?');">
			<input type="hidden" name="_token" value="${csrfToken}">
			<input type="hidden" name="_method" value="DELETE">
			<button type="submit" class="rounded-xl border border-orange-300 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50">Delete</button>
		</form>
	</div>
`;

const initProductsDataTable = () => {
	const tableElement = document.querySelector('#products-datatable');
	if (!tableElement) {
		return;
	}

	const searchInput = document.querySelector('#products-table-search');
	const categoryFilter = document.querySelector('#products-table-category');
	const statusFilter = document.querySelector('#products-table-status');
	const resetButton = document.querySelector('#products-table-reset');
	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

	const table = new DataTable(tableElement, {
		ajax: {
			url: tableElement.dataset.sourceUrl,
			data: (data) => {
				data.category_id = categoryFilter?.value ?? '';
				data.status = statusFilter?.value ?? '';
			},
		},
		processing: true,
		serverSide: true,
		deferRender: true,
		searchDelay: 350,
		pageLength: 12,
		lengthMenu: [12, 24, 50, 100],
		order: [[0, 'asc']],
		columns: [
			{ data: 'sku' },
			{
				data: 'barcode',
				render: (data, type) => {
					const barcode = data || '';
					if (type !== 'display') {
						return barcode;
					}

					return barcode === ''
						? '<span class="text-slate-400">-</span>'
						: `<span class="text-slate-700">${barcode}</span>`;
				},
			},
			{ data: 'name' },
			{ data: 'category' },
			{
				data: 'cost_price',
				className: 'text-right',
				render: (data, type) => {
					const numericValue = Number(data || 0);
					return type === 'display' ? currencyFormatter.format(numericValue) : numericValue;
				},
			},
			{
				data: 'sell_price',
				className: 'text-right',
				render: (data, type) => {
					const numericValue = Number(data || 0);
					return type === 'display' ? currencyFormatter.format(numericValue) : numericValue;
				},
			},
			{
				data: 'stock',
				className: 'text-right',
			},
			{
				data: 'status_label',
				render: (data, type, row) => {
					if (type !== 'display') {
						return data;
					}

					return row.is_active
						? '<span class="status-pill status-pill-safe">Active</span>'
						: '<span class="status-pill bg-slate-200 text-slate-700">Inactive</span>';
				},
			},
			{
				data: null,
				orderable: false,
				searchable: false,
				className: 'text-right',
				render: (_, __, row) => renderProductActions(row, csrfToken),
			},
		],
		language: {
			emptyTable: 'No products found.',
			zeroRecords: 'No products match your filters.',
			info: 'Showing _START_ to _END_ of _TOTAL_ products',
			infoEmpty: 'Showing 0 to 0 of 0 products',
			infoFiltered: '(filtered from _MAX_ total products)',
			lengthMenu: 'Rows per page _MENU_',
			paginate: {
				previous: '‹',
				next: '›',
			},
		},
	});

	const applyFilters = () => {
		const searchValue = searchInput?.value ?? '';

		table.search(searchValue);
		table.draw();
	};

	const debouncedSearch = debounce(applyFilters, 250);

	searchInput?.addEventListener('input', debouncedSearch);
	categoryFilter?.addEventListener('change', applyFilters);
	statusFilter?.addEventListener('change', applyFilters);
	resetButton?.addEventListener('click', () => {
		if (searchInput) {
			searchInput.value = '';
		}
		if (categoryFilter) {
			categoryFilter.value = '';
		}
		if (statusFilter) {
			statusFilter.value = '';
		}

		applyFilters();
	});
};

initProductsDataTable();
