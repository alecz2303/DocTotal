import './bootstrap';
import Swal from 'sweetalert2';
import { loadStripe } from '@stripe/stripe-js';

window.Swal = Swal;
window.loadStripe = loadStripe;