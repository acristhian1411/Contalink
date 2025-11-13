import { writable } from 'svelte/store';

function createSidebarStore() {
	const defaultValue = true;
	const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('sidebarOpen') : null;
	const initial = stored ? JSON.parse(stored) : defaultValue;
	
	const { subscribe, set, update } = writable(initial);
	
	return {
		subscribe,
		set: (value) => {
			if (typeof localStorage !== 'undefined') {
				localStorage.setItem('sidebarOpen', JSON.stringify(value));
			}
			set(value);
		},
		update: (fn) => {
			update((value) => {
				const newValue = fn(value);
				if (typeof localStorage !== 'undefined') {
					localStorage.setItem('sidebarOpen', JSON.stringify(newValue));
				}
				return newValue;
			});
		}
	};
}
export const sidebarOpen = createSidebarStore();
