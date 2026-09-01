import './bootstrap';
import Alpine from 'alpinejs';
import wmsDataTable from './data-table';
import './alert';
import './ajax-modal';
import './smart-picker';
import './form-autosave';
import './app-shell';

window.Alpine = Alpine;
window.wmsDataTable = wmsDataTable;
Alpine.data('wmsDataTable', wmsDataTable);
Alpine.start();
