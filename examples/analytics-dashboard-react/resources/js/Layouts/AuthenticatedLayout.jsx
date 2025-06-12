import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { 
    ChartBarIcon, 
    CubeIcon, 
    UserGroupIcon, 
    SparklesIcon,
    ChartPieIcon,
    Bars3Icon,
    XMarkIcon
} from '@heroicons/react/24/outline';

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: ChartPieIcon },
    { name: 'Sales Analytics', href: '/analytics/sales', icon: ChartBarIcon },
    { name: 'Customer Analytics', href: '/analytics/customers', icon: UserGroupIcon },
    { name: 'Product Analytics', href: '/analytics/products', icon: CubeIcon },
    { name: 'Advanced Analytics', href: '/analytics/advanced', icon: SparklesIcon },
];

export default function AuthenticatedLayout({ user, header, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Mobile sidebar */}
            <div className={`relative z-50 lg:hidden ${sidebarOpen ? '' : 'hidden'}`} role="dialog" aria-modal="true">
                <div className="fixed inset-0 bg-gray-900/80"></div>
                <div className="fixed inset-0 flex">
                    <div className="relative mr-16 flex w-full max-w-xs flex-1">
                        <div className="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button 
                                type="button" 
                                className="-m-2.5 p-2.5"
                                onClick={() => setSidebarOpen(false)}
                            >
                                <span className="sr-only">Close sidebar</span>
                                <XMarkIcon className="h-6 w-6 text-white" aria-hidden="true" />
                            </button>
                        </div>
                        <div className="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div className="flex h-16 shrink-0 items-center">
                                <h1 className="text-xl font-bold text-gray-900">Analytics Dashboard</h1>
                            </div>
                            <nav className="flex flex-1 flex-col">
                                <ul role="list" className="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {navigation.map((item) => {
                                                const isActive = window.location.pathname === item.href;
                                                return (
                                                    <li key={item.name}>
                                                        <Link
                                                            href={item.href}
                                                            className={`
                                                                group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold
                                                                ${isActive 
                                                                    ? 'bg-gray-50 text-indigo-600' 
                                                                    : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50'
                                                                }
                                                            `}
                                                        >
                                                            <item.icon
                                                                className={`h-6 w-6 shrink-0 ${isActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600'}`}
                                                                aria-hidden="true"
                                                            />
                                                            {item.name}
                                                        </Link>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            {/* Static sidebar for desktop */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
                <div className="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6">
                    <div className="flex h-16 shrink-0 items-center">
                        <h1 className="text-xl font-bold text-gray-900">
                            <span className="text-indigo-600">Laraduck</span> Analytics
                        </h1>
                    </div>
                    <nav className="flex flex-1 flex-col">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            <li>
                                <ul role="list" className="-mx-2 space-y-1">
                                    {navigation.map((item) => {
                                        const isActive = window.location.pathname === item.href;
                                        return (
                                            <li key={item.name}>
                                                <Link
                                                    href={item.href}
                                                    className={`
                                                        group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-colors
                                                        ${isActive 
                                                            ? 'bg-gray-50 text-indigo-600' 
                                                            : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50'
                                                        }
                                                    `}
                                                >
                                                    <item.icon
                                                        className={`h-6 w-6 shrink-0 transition-colors ${isActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600'}`}
                                                        aria-hidden="true"
                                                    />
                                                    {item.name}
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </li>
                            <li className="-mx-6 mt-auto">
                                <div className="flex items-center gap-x-4 px-6 py-3 text-sm font-semibold leading-6 text-gray-900 border-t border-gray-200">
                                    <div className="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center">
                                        <UserGroupIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <span className="sr-only">Your profile</span>
                                    <span aria-hidden="true">Demo User</span>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div className="lg:pl-72">
                {/* Mobile menu button */}
                <div className="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    <button 
                        type="button" 
                        className="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <span className="sr-only">Open sidebar</span>
                        <Bars3Icon className="h-6 w-6" aria-hidden="true" />
                    </button>

                    <div className="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true"></div>

                    <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                        <div className="flex items-center gap-x-4 lg:gap-x-6">
                            {header && (
                                <div className="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true"></div>
                            )}
                        </div>
                    </div>
                </div>

                <main className="py-10">
                    <div className="px-4 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}