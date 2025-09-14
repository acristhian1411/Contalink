<script>
    import {Inertia} from '@inertiajs/inertia';
    import { CartIcon, MoneyIcon, ProductIcon, BarsIcon, DevolutionIcon} from '@components/Icons/';
    function goTo(url) {
      Inertia.visit(url);
    }
  </script>
  
  <div class="grid grid-cols-2 gap-6 p-8">
    
    <button class="flex flex-col items-center justify-center p-6 bg-green-600 text-white rounded-lg shadow-md hover:bg-green-700" on:click={() => goTo('/create-sales')}>
      <!-- SVG Icon for Ventas -->
      <CartIcon/>
      <span>Ventas</span>
    </button>
    <button class="flex flex-col items-center justify-center p-6 bg-blue-600 text-white rounded-lg shadow-md hover:bg-blue-700" on:click={() => goTo('/create-purchase')}>
      <!-- SVG Icon for Compras -->
      <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-2 11H8a2 2 0 00-2 2v1h12v-1a2 2 0 00-2-2z" />
      </svg>
      <span>Compras</span>
    </button>
  
  
    <button class="flex flex-col items-center justify-center p-6 bg-purple-600 text-white rounded-lg shadow-md hover:bg-purple-700" on:click={() => goTo('/create-refunds')}>
      <!-- SVG Icon for Productos -->
        <DevolutionIcon
          className="w-12 h-12 mb-2"
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
        />
      <span>Devoluciones</span>
    </button>

    <button class="flex flex-col items-center justify-center p-6 bg-pink-600 text-white rounded-lg shadow-md hover:bg-pink-700" on:click={() => goTo('/products')}>
      <!-- SVG Icon for Productos -->
        <ProductIcon
          className="w-12 h-12 mb-2"
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
        />
      <span>Productos</span>
    </button>
  <button class="flex flex-col items-center justify-center p-6 bg-yellow-600 text-white rounded-lg shadow-md hover:bg-yellow-700" on:click={() => goTo('/tills')}>
      <!-- SVG Icon for Cajas -->
      <MoneyIcon/>
      <span>Cajas</span>
    </button>
    <button class="flex flex-col items-center justify-center p-6 bg-red-600 text-white rounded-lg shadow-md hover:bg-red-700" on:click={() => goTo('/reports')}>
      <!-- SVG Icon for Cajas -->
      <BarsIcon
        className="w-12 h-12 mb-2"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="1"
      />
      <span>Informes</span>
    </button>

  </div>
  