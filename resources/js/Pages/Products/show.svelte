<script>
    import { onMount } from 'svelte';
    import {blur} from 'svelte/transition';
    import axios from 'axios';
    import { router } from '@inertiajs/svelte';
    import JsBarcode from 'jsbarcode';
    export let id = 0;
    export let product;
    export let audits;
    let barcodeElement
    let error = null;
    let url = `/api/products/`;
    let barcode_copies = 1

    function print(){
        if (barcodeElement && barcode_copies > 0) {
      const svgContent = barcodeElement.outerHTML;
      const printWindow = window.open("", "_blank");

      if (printWindow) {
        let repeatedContent = "";
        for (let i = 0; i < barcode_copies; i++) {
          repeatedContent += `
            <div class="barcode-container">
              <div class="description">${product.product_name}</div>
              ${svgContent}
            </div>
          `;
        }

        printWindow.document.write(`
          <html>
            <head>
              <title>Imprimir Código de Barras</title>
              <style>
                body {
                  font-family: Arial, sans-serif;
                  text-align: center;
                  margin: 0;
                  padding: 10px;
                  display: grid;
                  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                  gap: 10px;
                }
                .grid-container {
                  display: grid;
                  grid-template-columns: repeat(4, 1fr); /* Máximo 4 elementos por fila */
                  gap: 20px;
                }
                .barcode-container {
                  text-align: center;
                  page-break-inside: avoid;
                }
                svg {
                  margin: 10px auto;
                  display: block;
                }
                .description {
                  font-size: 12px;
                  margin-bottom: 5px;
                }
              </style>
            </head>
            <body>
              <div class="grid-container">
                ${repeatedContent}
              </div>
            </body>
          </html>
        `);
        printWindow.document.close();
        printWindow.onload = () => {
          printWindow.print();
          printWindow.close();
        };
      }
    }
    }

    onMount(async () => {
        // fetchData();
        if(barcodeElement){
          JsBarcode(barcodeElement, `${product.product_barcode}`, {
              format: 'CODE128',
              displayValue: false,
              margin: 1,
              height: 50,
              width: 2
          });
        }
    });
    function goTo(route){
        router.visit(route);
    }
</script>
<svelte:head>
    <title>{product?.product_name}</title>
</svelte:head>
{#if error}
	<p>{error}</p>
{/if}
<div class="breadcrumbs text-md mb-4">
	<ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
		<li><span class="cursor-pointer" on:click={()=>goTo("/")}>Inicio</span></li>
		<!-- svelte-ignore a11y-click-events-have-key-events -->
		<li><span class="cursor-pointer" on:click={()=>goTo("/products")}>Productos</span></li>
	</ul>
</div>
{#if product}
    <div transition:blur>
        <h1 class="text-xl font-bold">Nombre:</h1>
        <p class="text-1xl">{product.product_name}</p>
        <h1 class="text-xl font-bold">Descripcion:</h1>
        <p class="text-1xl">{product.product_desc}</p>
        <h1 class="text-xl font-bold">Código:</h1>
        <p class="text-1xl">{product.product_barcode}</p>
        <h1 class="text-xl font-bold">Precio de costo:</h1>
        <p class="text-1xl">{product.product_cost_price}</p>
        <h1 class="text-xl font-bold">Porcentaje de ganancias:</h1>
        <p class="text-1xl">{product.product_profit_percent}</p>
        <h1 class="text-xl font-bold">Precio de venta:</h1>
        <p class="text-1xl">{product.product_selling_price}</p>

        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <div on:click={print}>
            <svg  bind:this={barcodeElement}></svg>
            
        </div>
        <input type="number" min="1" bind:value={barcode_copies} placeholder="Número de copias" />
    </div>
{/if}
{#if audits}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">Detalles de cambios:</h1>
        <table class="table w-full">
            <thead>
                <tr>
                    <!-- <th>Id</th> -->
                    <th class="text-center text-lg">Usuario</th>
                    <th class="text-center text-lg">Evento</th>
                    <th class="text-center text-lg">Fecha</th>
                    <th class="text-center text-lg">Detalles</th>
                </tr>
            </thead>
            <tbody>
                {#each audits as audit}
                    <tr>
                        <td class="text-center">{audit.user?audit.user.name:''}</td>
                        <td class="text-center">{audit.event}</td>
                        <td class="text-center">{new Date(audit.created_at).toLocaleString()}</td>
                        <td class="text-center">
                            <strong>Valores antiguos:</strong> {JSON.stringify(audit.old_values)}<br>
                            <strong>Valores nuevos:</strong> {JSON.stringify(audit.new_values)}
                        </td>
    
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}