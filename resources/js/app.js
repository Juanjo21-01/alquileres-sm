import {
    Chart,
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    LinearScale, CategoryScale,
    Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    LinearScale, CategoryScale,
    Tooltip, Legend, Filler,
);

// Disponible globalmente para inicializar gráficos desde componentes Livewire (Alpine).
window.Chart = Chart;
