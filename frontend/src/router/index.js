import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../views/Dashboard.vue';
import Orders from '../views/Orders.vue';
import Transfers from '../views/Transfers.vue';
import Parameters from '../views/Parameters.vue';
import Import from '../views/Import.vue';
import HowItWorks from '../views/HowItWorks.vue';

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard
  },
  {
    path: '/orders',
    name: 'Orders',
    component: Orders
  },
  {
    path: '/transfers',
    name: 'Transfers',
    component: Transfers
  },
  {
    path: '/parameters',
    name: 'Parameters',
    component: Parameters
  },
  {
    path: '/import',
    name: 'Import',
    component: Import
  },
  {
    path: '/how-it-works',
    name: 'HowItWorks',
    component: HowItWorks
  }
];

const router = createRouter({
  history: createWebHistory('/rev3/'),
  routes
});

export default router;
