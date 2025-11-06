<script>
    import { createEventDispatcher } from 'svelte';
	import {SearchIcon, SortIcon} from '@components/Icons/';
    import axios from 'axios';
    import { onMount } from 'svelte';

    const dispatch = createEventDispatcher();

	let search_param = '';
    export let label;
    export let type
    let data = [];

    function selectPerson(event) {
        let person = event;
        dispatch('selectPerson', person);
    }

    async function getPersons() {
        const apiHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
        axios.get(`/api/search/persons-by-type/${type}`,{
            params:{q:search_param},
            headers: apiHeaders
    }).then((response) => {
            data = response.data.data;
        }).catch((err) => {
            let detail = {
                detail: {
                    type: 'delete',
                    message: err.response.data.message
                }
            };
        });
    }

    function sortData(param) {

    }

    function search(event) {
		search_param = event.target.value;
		getPersons();
	}

    onMount(async () => {
        getPersons();
    });

</script>
<div>
    <h3 class="mb-4 text-center text-2xl">Buscar {label}</h3>
    <div class="flex justify-center">
        <label class="input input-bordered flex items-center gap-2">
            <input type="text" class="grow" placeholder="buscar" on:change={search} />
            <SearchIcon />
        </label>
    </div>
    <table class="table w-full">
        <thead>
            <tr>
                <th class="text-center text-lg">
                    
                    <div class="flex items-center">
                        id
                        <button on:click={() => sortData('id')}
                            ><SortIcon/></button
                        >
                        </div>
                </th>
                <th class="text-center text-lg" on:click={() => sortData('person_lastname')}>
                    <div class="flex items-center justify-center">
                        {label}
                        <button><SortIcon/></button>
                    </div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">
                        Cedula
                        <button on:click={() => sortData('person_idnumber')}
                            ><SortIcon/></button
                        >
                    </div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">
                        Ruc
                        <button on:click={() => sortData('person_ruc')}
                            ><SortIcon/></button
                        >
                    </div>
                </th>
                
            </tr>
        </thead>
        <tbody>
            {#each data as person, i (person.id)}
                <tr class="hover">
                    <td>{person.id}</td>
                    <td class="text-center">{person.person_fname + ' ' + person.person_lastname}</td>
                    <td class="text-center">{person.person_idnumber}</td>
                    <td class="text-center">{person.person_ruc}</td>
                    <td>
                        <button 
                            class="btn btn-primary"
                            on:click={() => selectPerson({ person,
                            label: (person.person_fname + ' ' + person.person_lastname)
                            })}
                        >
                            Seleccionar
                        </button>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>