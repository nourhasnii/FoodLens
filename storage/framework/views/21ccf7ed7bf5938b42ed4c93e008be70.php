<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'NutriTrace')); ?></title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;0,9..144,800;0,9..144,900;1,9..144,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-inter text-ink antialiased bg-cream">
        <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
            <aside class="relative hidden min-h-screen h-full overflow-hidden bg-[#1F3D2E] px-8 py-12 md:flex md:items-center md:justify-center lg:px-16">
                <svg class="absolute top-10 left-10 w-20 h-20 text-white/15 pointer-events-none" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.2">
                    <path d="M50 12 C52 4, 58 2, 58 10 C64 4, 72 8, 70 18 C80 22, 78 32, 70 34 C74 44, 66 54, 58 50 C54 58, 46 58, 42 50 C34 54, 26 44, 30 34 C22 32, 20 22, 30 18 C28 8, 36 4, 42 10 C42 2, 48 4, 50 12 Z"/>
                    <circle cx="50" cy="30" r="3.5"/>
                    <path d="M50 50 L48 72 M42 54 L28 70 M58 54 L72 70"/>
                </svg>

                <svg class="absolute bottom-16 right-10 w-28 h-20 text-white/10 pointer-events-none -rotate-6" viewBox="0 0 60 110" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path d="M30 4 C12 14, 0 46, 10 86 C14 100, 30 106, 36 94 C40 104, 52 106, 58 92 C64 72, 58 40, 44 22 C38 8, 30 2, 30 4 Z"/>
                    <path d="M30 16 C28 42, 24 64, 18 86" stroke-width="1"/>
                    <path d="M30 34 C38 38, 44 48, 46 58" stroke-width="1"/>
                    <path d="M30 56 C22 62, 18 72, 16 80" stroke-width="1"/>
                </svg>

                <div class="relative z-10 w-full max-w-md">
                    <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-2.5 mb-12">
                        <span class="w-9 h-9 rounded-full bg-amber-warm flex items-center justify-center shadow-md shadow-amber-warm/40 shrink-0">
                            <svg class="w-4.5 h-4.5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L14.09 8.26L21 9.27L16 14.14L17.18 21.02L12 17.77L6.82 21.02L8 14.14L3 9.27L9.91 8.26L12 2Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="font-fraunces font-extrabold text-2xl text-white tracking-tight">NutriTrace</span>
                    </a>

                    <div>
                        <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="2" height="2" rx="0.5"/>
                                <rect x="17" y="14" width="2" height="2" rx="0.5"/>
                                <rect x="14" y="17" width="2" height="2" rx="0.5"/>
                                <rect x="17" y="17" width="2" height="2" rx="0.5"/>
                                <rect x="19" y="19" width="2" height="2" rx="0.5"/>
                            </svg>
                        </div>
                        <h2 class="font-fraunces font-bold text-3xl xl:text-4xl text-white leading-tight mb-5">Chaque produit a une histoire vérifiable.</h2>
                        <p class="text-white/70 text-base leading-relaxed">Connectez-vous ou créez un compte pour suivre la traçabilité de vos produits, de la ferme jusqu'à votre assiette, avec des preuves documentées à chaque étape.</p>
                    </div>
                </div>
            </aside>

            <main class="flex min-h-screen items-center justify-center bg-[#FAFAF8] px-8 py-12 lg:px-16">
                <div class="w-full max-w-lg">
                    <div class="mb-8 flex justify-center md:hidden">
                        <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-full bg-amber-warm flex items-center justify-center shadow-md shadow-amber-warm/40 shrink-0">
                                <svg class="w-4.5 h-4.5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L14.09 8.26L21 9.27L16 14.14L17.18 21.02L12 17.77L6.82 21.02L8 14.14L3 9.27L9.91 8.26L12 2Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="font-fraunces font-extrabold text-xl text-ink tracking-tight">NutriTrace</span>
                        </a>
                    </div>
                    <?php echo e($slot); ?>

                </div>
            </main>
        </div>
    </body>
</html>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/layouts/guest.blade.php ENDPATH**/ ?>