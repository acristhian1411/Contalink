<script>

    // import {logout} from '../../services/authservice.js'
	import {Home,BoxesIcon, ExitIcon, ConfigIcon, FinanceIcon, LocationIcon, UserIcon, ReportIcon, ProductIcon, PeopleIcon} from '@components/Icons/';
	import { router } from '@inertiajs/svelte';
    import DropdownMenu from './DropdownMenu.svelte';
	import {locationItems,configItems,adminItems,userItems,reportsItems, productsItems, personsItems} from './MenuItems.js';
	import { sidebarOpen } from './sidebar.js';
	import { onMount } from 'svelte';
	export let user;

	let isOpen

	onMount(() => {
		sidebarOpen.subscribe(value => {
			isOpen = value;
		});
	});

	function goTo(route){
		router.visit(route);
	}

	function logout() {
		router.post('/logout');
		// router.visit('/');
	}

</script>
<aside
	id="logo-sidebar"
	class={`fixed left-0 top-0 z-40 h-screen w-64 border-r border-gray-200 bg-white pt-20 transition-transform dark:border-gray-700 dark:bg-gray-800 ${
		isOpen ? 'translate-x-0' : '-translate-x-full'
	  }`}
	aria-label="Sidebar"
>
	<div class="h-full overflow-y-auto bg-white px-3 pb-4 dark:bg-gray-800">
		<ul class="space-y-2 font-medium">
			<li>
				<!-- svelte-ignore a11y-click-events-have-key-events -->
				<span
					on:click={()=>goTo("/")}
					class="group flex items-center cursor-pointer rounded-lg p-2 text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
				>
					<Home/>
					<span class="ml-3">Inicio</span>
				</span>
			</li>
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('locations.index')}
				<DropdownMenu title="Ubicaciones" items={locationItems(user)}>
					<LocationIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('configuration.index')}
				<DropdownMenu title="Configuraciones generales" items={configItems(user)}>
					<ConfigIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('products.sidebar')}
				<DropdownMenu title="Productos" 
				items={productsItems(user)}>
				<ProductIcon 
					className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
				/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('administration.index')}
				<DropdownMenu title="Administración" items={adminItems(user)}>
					<FinanceIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('persons.index')}
				<DropdownMenu title="Personas" items={personsItems(user)}>
					<PeopleIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('reports.index')}
				<DropdownMenu title="Reportes" items={reportsItems(user)}>
					<ReportIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			{#if user != undefined && user.permissions != undefined && user.permissions.includes('users.index')}
				<DropdownMenu title="Usuarios" items={userItems(user)}>
					<UserIcon 
						className="h-5 w-5 flex-shrink-0 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" 
					/>
				</DropdownMenu>
			{/if}
			<li>
				<!-- svelte-ignore a11y-click-events-have-key-events -->
				<span
					on:click={()=>logout()}
					class="group flex items-center cursor-pointer rounded-lg p-2 text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
				>
					<ExitIcon/>
					<span class="ml-3 flex-1 whitespace-nowrap">Cerrar sesión</span>
				</span>
			</li>
			
		</ul>
	</div>
</aside>