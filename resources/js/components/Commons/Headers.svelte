<script>
	// import {getToken, getUserData} from '../../services/authservice.js'
	import axios from 'axios';
	import { onMount } from 'svelte';
	import { sidebarOpen } from './sidebar.js';
	import { Link } from '@inertiajs/svelte';
	const company_name = import.meta.env.VITE_COMPANY_NAME;

	export let user;
	let token;
	let config;
	let person
	let person_photo = '';

	function toggleSidebar() {
		sidebarOpen.update(value => !value);
	}

	function getPerson() {
		axios
			.get(`/api/persons/${user.person_id}`,config)
			.then((res) => {
				person = res.data;
				person_photo = '/images/'+person.person_photo;
			})
			.catch((err) => {
				console.log(err);
			});
	}

	onMount(() => {
		token = '';
		user = [];
		config = {
			headers: {}
		};
		// getPerson();
	});
</script>

<nav
	class="fixed top-0 z-50 w-full border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
>
	<div class="px-3 py-3 lg:px-5 lg:pl-3">
		<div class="flex items-center justify-between">
			<div class="flex items-center justify-start">
				<button
					on:click={toggleSidebar}
					data-drawer-target="logo-sidebar"
					data-drawer-toggle="logo-sidebar"
					aria-controls="logo-sidebar"
					type="button"
					class="inline-flex items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 "
				>
					<span class="sr-only">Open sidebar</span>
					<svg
						class="h-6 w-6"
						aria-hidden="true"
						fill="currentColor"
						viewBox="0 0 20 20"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							clip-rule="evenodd"
							fill-rule="evenodd"
							d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"
						></path>
					</svg>
				</button>
				<Link href="/"
					class="ml-3 flex md:mr-24"
				>
					<img src="/img/logo.webp" class="mr-2 h-10 w-10 rounded-full" alt="Company Logo" />
					<span
						class="self-center whitespace-nowrap text-xl font-semibold dark:text-white sm:text-2xl"
						>{company_name}</span>
				</Link>
			</div>
			<div class="flex items-center">
				<div class="ml-3 flex items-center">
					<div>
						<button
							type="button"
							class="flex rounded-full bg-gray-800 text-sm focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
							aria-expanded="false"
							data-dropdown-toggle="dropdown-user"
						>
							<span class="sr-only">Open user menu</span>
							<!-- svelte-ignore a11y-img-redundant-alt -->
							<img
								class="h-8 w-8 rounded-full"
								src="/img/default-avatar.jpg"
								alt="user photo"
							/>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</nav>