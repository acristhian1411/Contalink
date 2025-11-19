import { BoxesIcon } from '@components/Icons/';

export const personsItems =(user)=> [
    {
        label: 'Proveedores',
        route: '/providers',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('persons.index')
    },
    {
        label: 'Empleados',
        route: '/employees',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('employees.index')
    },
    {
        label: 'Clientes',
        route: '/clients',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('clients.index')
    }
];

export const locationItems =(user)=> [
    {
        label: 'Paises',
        route: '/countries',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('countries.index')
    },
    {
        label: 'Departamentos',
        route: '/states',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('states.index')
    },
    {
        label: 'Ciudades',
        route: '/cities',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('cities.index')
    }
];
export const configItems =(user)=> [
    {
        label: 'Tipos de cajas',
        route: '/till-types',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('tilltypes.index')
    },
    // {
    //     label: 'Tipos de IVA',
    //     route: '/iva-types',
    //     icon: BoxesIcon,
    //     permission: user != undefined && user.permissions != undefined && user.permissions.includes('ivatypes.index')
    // },
    // {
    //     label: 'Tipo de Personas',
    //     route: '/person-types',
    //     icon: BoxesIcon,
    //     permission: user != undefined && user.permissions != undefined && user.permissions.includes('persontypes.index')
    // },
    {
        label: 'Tipos de pago',
        route: '/payment-types',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('paymenttypes.index')
    },
    {
        label: 'Tipos de contacto',
        route: '/contacttypes',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('contacttypes.index')
    },
    {
        label: 'Unidades de medida',
        route:'/measurement-units',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('measurement_units.index')
    }
];
export const productsItems =(user)=> [
    {
        label: 'Categorias',
        route: '/categories',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('categories.index')
    },
    {
        label: 'Marcas',
        route: '/brands',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('brands.index')
    },
    {
        label: 'Productos',
        route: '/products',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('products.index')
    }
];
export const adminItems =(user)=> [
    {
        label:'Cajas',
        route: '/tills',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('tills.index')
    },
    {
        label: 'Compras',
        route: '/purchases',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('purchases.index')
    },
    {
        label: 'Ventas',
        route: '/sales',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('sales.index')
    },
    {
        label: 'Devoluciones',
        route: '/refunds',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('sales.index')
    }
];
export const reportsItems =(user)=> [
    {
        label: 'Reportes',
        route: '/reports',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('reports.index')
    }
];
export const userItems =(user)=> [
    {
        label: 'Roles',
        route: '/roles',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('roles.index')
    },
    {
        label: 'Usuarios',
        route: '/users',
        icon: BoxesIcon,
        permission: user != undefined && user.permissions != undefined && user.permissions.includes('users.index')
    }
];