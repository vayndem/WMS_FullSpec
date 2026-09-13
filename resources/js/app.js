import './bootstrap';
import Alpine from 'alpinejs';
import wmsDataTable from './data-table';
import taskChart from './task-chart';
import notifikasiLonceng from './notification-bell';
import './format';
import './alert';
import './ajax-modal';
import './smart-picker';
import './number-format';
import './form-autosave';
import './app-shell';

window.Alpine = Alpine;
window.wmsDataTable = wmsDataTable;
Alpine.data('wmsDataTable', wmsDataTable);
Alpine.data('taskChart', taskChart);
Alpine.data('notifikasiLonceng', notifikasiLonceng);
Alpine.start();
