<x-card :title="__('CDN Actions', 'performance-toolkit')" id="ptk-cdn-actions">

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div class="flex min-h-44 gap-4 rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <!-- Lucide icon: shield-check -->
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                <h3 class="m-0 text-base font-semibold leading-snug text-slate-900">
                    Test Connection
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Verify your CDN credentials and confirm communication with the provider API.
                </p>

                <div class="mt-auto pt-5">
                    <button type="button" class="inline-flex min-h-9 items-center rounded-md border border-blue-600 bg-white px-4 text-sm font-medium text-blue-600 hover:bg-blue-50">
                        Test Connection
                    </button>
                </div>
            </div>
        </div>
    </div>


    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
        <input type="hidden" name="action" value="{{ esc_attr($test_action) }}" />
        @php
            wp_nonce_field('ptk_cloudflare_test');
            submit_button(__('Test connection', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>




    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top:10px;">
        <input type="hidden" name="action" value="{{ esc_attr($purge_action) }}" />
        @php
            wp_nonce_field('ptk_cloudflare_purge');
            submit_button(__('Purge cache', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>



</x-card>

