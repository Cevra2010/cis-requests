import './bootstrap';
import 'flowbite';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

// html5-qrcode (Kamera-QR-Scanner, siehe Modules/Lager) ist eine recht große
// Bibliothek, die nur die wenigsten Seiten tatsächlich brauchen – bewusst per
// dynamic import() in einen eigenen, erst bei Bedarf nachgeladenen Chunk
// ausgelagert statt sie in jedem Seitenaufruf mitzuladen.
window.loadHtml5Qrcode = () => import('html5-qrcode').then((m) => m.Html5Qrcode);
