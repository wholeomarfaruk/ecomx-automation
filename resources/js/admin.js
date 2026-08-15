// AlpineJs ==================================================
// import Alpine from 'alpinejs'
// window.Alpine = Alpine


// Jquery ==================================================
import $ from 'jquery';
window.$ = $;
window.jQuery = $;


// SwiperJs==================================================
// core version + navigation, pagination modules:
// import Swiper bundle with all modules installed
import Swiper from 'swiper/bundle';

// import styles bundle
import 'swiper/css/bundle';

window.Swiper = Swiper;

// SplideJs================================================

import Splide from '@splidejs/splide';
import '@splidejs/splide/css'; // default theme



//Filepond ===================================================START

import * as FilePond from 'filepond';
import 'filepond/dist/filepond.min.css';

import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';

// expose to window for Alpine / Livewire
window.FilePond = FilePond;

// Register plugins
FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateType
);

// Initialize
// document.addEventListener('DOMContentLoaded', function () {
//     console.log('Filepond');
//     console.log(FilePond);

//     document.querySelectorAll('.filepond').forEach(input => {

//         FilePond.create(input, {
//             allowMultiple: true,
//             acceptedFileTypes: ['image/*'],
//             server: {
//                 process: {
//                     url: '/admin/upload',
//                     method: 'POST',
//                     headers: {
//                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//                     }
//                 },

//                 revert: {
//                     url: '/admin/upload/revert',
//                     method: 'DELETE',
//                     headers: {
//                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//                     }
//                 }
//             }
//         });

//     });

// });
// Filepond ===================================================END
//-------------------------------------------------------------
//-------------------------------------------------------------
//Sweetalert2==================================================START
import Swal from 'sweetalert2';

window.Swal = Swal;

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})
window.Toast = Toast;
document.addEventListener('livewire:init', () => {

    Livewire.on('show-delete-confirmation', () => {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed');
            }
        });
    });

});

//Sweetalert2==================================================END
//-------------------------------------------------------------
//-------------------------------------------------------------
//Notyf==================================================START
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

window.notyf = new Notyf({
    duration: 3000,
    position: {
        x: 'right',
        y: 'top',
    },
});
document.addEventListener('livewire:init', () => {

    Livewire.on('notify', ({ type, message }) => {
        if (type === 'success') {
            notyf.success(message);
        } else {
            notyf.error(message);
        }
    });

});

//Notyf==================================================END
//-------------------------------------------------------------
//-------------------------------------------------------------
//FlatPickr==================================================START
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

window.flatpickr = flatpickr;

window.initFlatpickr = () => {
    document.querySelectorAll(".flatpickr-only-date").forEach((el) => {
        if (el._flatpickr) el._flatpickr.destroy();
        flatpickr(el, {
            enableTime: false,
            dateFormat: "Y-m-d",
            defaultDate: el.value || null,
            onChange: function () {
                el.dispatchEvent(new Event("input", { bubbles: true }));
            },
        });
    });

    document.querySelectorAll(".flatpickr").forEach((el) => {
        if (el._flatpickr) el._flatpickr.destroy();
        let defaultDate = el.value;
        if (defaultDate && defaultDate.length === 10) {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            defaultDate += ` ${hours}:${minutes}`;
        }
        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i:S",
            enableSeconds: true,
            defaultDate: defaultDate || new Date(),
            minuteIncrement: 1,
            onChange: function () {
                el.dispatchEvent(new Event("input", { bubbles: true }));
            },
        });
    });
};

document.addEventListener("DOMContentLoaded", () => window.initFlatpickr());
document.addEventListener("livewire:navigated", () => window.initFlatpickr());
document.addEventListener("livewire:initialized", () => {
    Livewire.hook("morph.updated", () => window.initFlatpickr());
});

//FlatPickr==================================================END
//--    ====================================================
//Sortable==================================================START
import Sortable from 'sortablejs';

window.Sortable = Sortable;

//Sortable==================================================END
//--    ====================================================
//Boxicons==================================================START
// import 'boxicons/css/boxicons.min.css';

//Boxicons==================================================END
// fancybox==================================================START
import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";

window.Fancybox = Fancybox;

// Bind once — Fancybox uses event delegation on document so dynamic elements work automatically.
// Do NOT re-bind on Livewire morphs; it would stack duplicate listeners.
document.addEventListener("DOMContentLoaded", () => {
    Fancybox.bind("[data-fancybox]", {
        groupAll: false,
        Toolbar: {
            display: {
                left:   ["infobar"],
                middle: [],
                right:  ["download", "close"],
            },
        },
    });
});

// Track open state so Escape / clicks don't bubble to parent Alpine modals
if (Fancybox.defaults) {
    Fancybox.defaults.on = {
        open:    () => { window._fancyboxOpen = true; },
        destroy: () => { setTimeout(() => { window._fancyboxOpen = false; }, 50); },
    };
}

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && window._fancyboxOpen) e.stopImmediatePropagation();
}, true);

document.addEventListener("click", (e) => {
    if (window._fancyboxOpen && e.target.closest(".fancybox__container")) e.stopPropagation();
}, true);
// fancybox==================================================END

// ChartJs==================================================START
import Chart from 'chart.js/auto';

window.Chart = Chart;

// ChartJs==================================================END
