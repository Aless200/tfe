import './styles/app.css';

import '@hotwired/stimulus';

import '@hotwired/turbo';

import 'flowbite';


document.addEventListener('turbo:load', () => {
    import('flowbite').then(() => {
        // Gestion des dropdowns avec data-dropdown-toggle
        const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
        dropdownButtons.forEach(button => {
            const targetId = button.getAttribute('data-dropdown-toggle');
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                button.addEventListener('click', () => {
                    targetElement.classList.toggle('hidden');
                });
            }
        });

        // Gestion des collapses avec data-collapse-toggle
        const collapseButtons = document.querySelectorAll('[data-collapse-toggle]');
        collapseButtons.forEach(button => {
            const targetId = button.getAttribute('data-collapse-toggle');
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                button.addEventListener('click', () => {
                    targetElement.classList.toggle('hidden');
                });
            }
        });

        // Gestion des onglets avec data-tabs-toggle
        const tabButtons = document.querySelectorAll('[data-tabs-toggle]');
        tabButtons.forEach(button => {
            const targetId = button.getAttribute('data-tabs-target');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                button.addEventListener('click', () => {
                    // Cacher tous les panneaux d'onglets
                    document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                        panel.classList.add('hidden');
                    });
                    // Afficher le panneau cible
                    targetElement.classList.remove('hidden');
                });
            }
        });
    });
});


// Modal Gallery
// function openModal(imageSrc) {
//     const modal = document.getElementById('imageModal');
//     const modalImage = document.getElementById('modalImage');
//     modalImage.src = imageSrc;
//     modal.classList.remove('hidden');
// }
//
// document.getElementById('closeModal').addEventListener('click', function () {
//     const modal = document.getElementById('imageModal');
//     modal.classList.add('hidden');
// });


