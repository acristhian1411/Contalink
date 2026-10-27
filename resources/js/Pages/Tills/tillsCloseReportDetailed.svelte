<script>
    import { formatNumber } from '@components/utilities/NumberFormat.js';

    export let till;
    export let closeReport;
</script>

  <div class="p-6 bg-base-200 rounded-lg shadow-md space-y-6">
    <h2 class="text-2xl font-bold mb-4">Detalles del Cierre de Caja</h2>
  
    <!-- Tabla de Ingresos -->
    <div>
      <h3 class="text-xl font-semibold mb-3">Ingresos</h3>
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th class="text-left">Fecha</th>
              <th class="text-left">Cliente</th>
              <th class="text-left">Monto</th>
              <th class="text-left">Descripción</th>
            </tr>
          </thead>
          <tbody>
            {#if closeReport.incomes.length > 0}
              {#each closeReport.incomes as income}
                <tr>
                  <td>{income.td_date}</td>
                  <td>{income.person_fname} {income.person_lastname}</td>
                  <td class="text-green-500">Gs. {formatNumber(income.td_amount)}</td>
                  <td>{income.td_desc}</td>
                </tr>
              {/each}
              <tr>
                <td colspan="4" class="text-center">Total: Gs. {formatNumber(closeReport.incomes.reduce((acc, curr) => acc + curr.td_amount, 0))}</td>
              </tr>
            {:else}
              <tr>
                <td colspan="4" class="text-center">No hay ingresos registrados</td>
              </tr>
            {/if}
          </tbody>
        </table>
      </div>
    </div>
  
    <!-- Tabla de Gastos -->
    <div>
      <h3 class="text-xl font-semibold mb-3">Gastos</h3>
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th class="text-left">Fecha</th>
              <th class="text-left">Proveedor</th>
              <th class="text-left">Monto</th>
              <th class="text-left">Descripción</th>
            </tr>
          </thead>
          <tbody>
            {#if closeReport.expenses.length > 0}
              {#each closeReport.expenses as expense}
                <tr>
                  <td>{expense.td_date}</td>
                  <td>{expense.person_fname} {expense.person_lastname}</td>
                  <td class="text-red-500">Gs. {formatNumber(expense.td_amount)}</td>
                  <td>{expense.td_desc}</td>
                </tr>
              {/each}
              <tr>
                <td colspan="4" class="text-center">Total: Gs. {formatNumber(closeReport.expenses.reduce((acc, curr) => acc + curr.td_amount, 0))}</td>
              </tr>
            {:else}
              <tr>
                <td colspan="4" class="text-center">No hay gastos registrados</td>
              </tr>
            {/if}
          </tbody>
        </table>
      </div>
    </div>
  </div>
