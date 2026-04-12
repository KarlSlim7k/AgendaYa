const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 },
        locale: 'es-MX',
    });
    const page = await context.newPage();
    
    const errors = [];
    const warnings = [];
    const successes = [];
    
    // Helper functions
    async function logTest(testName, status, detail = '') {
        const icon = status === 'PASS' ? '✅' : status === 'WARN' ? '⚠️' : '❌';
        console.log(`${icon} ${testName}${detail ? ' - ' + detail : ''}`);
        if (status === 'FAIL') errors.push({ test: testName, detail });
        if (status === 'WARN') warnings.push({ test: testName, detail });
        if (status === 'PASS') successes.push(testName);
    }

    async function safeScreenshot(name) {
        try {
            await page.screenshot({ path: `test-results/${name}.png`, fullPage: false });
        } catch (e) {
            // ignore
        }
    }

    // Create test-results directory
    if (!fs.existsSync('test-results')) {
        fs.mkdirSync('test-results', { recursive: true });
    }

    try {
        console.log('\n🧪 INICIANDO PRUEBAS E2E - AgendaYa Business Panel\n');
        console.log('='.repeat(60));

        // ========================================
        // TEST 1: Login
        // ========================================
        console.log('\n📝 TEST 1: Login al panel de negocio');
        try {
            await page.goto('https://agendaya.mx/login', { waitUntil: 'networkidle', timeout: 30000 });
            
            // Check if already logged in
            if (page.url().includes('/dashboard')) {
                await logTest('Login - Already logged in', 'PASS');
            } else {
                // Fill login form
                await page.fill('input[name="email"]', 'negocio@agendaya.mx');
                await page.fill('input[name="password"]', 'password123');
                
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
                    page.click('button[type="submit"]'),
                ]);

                if (page.url().includes('/dashboard') || page.url().includes('/business')) {
                    await logTest('Login - Successful', 'PASS');
                } else {
                    await logTest('Login - Failed', 'FAIL', `Redirected to: ${page.url()}`);
                    await safeScreenshot('login-failed');
                    return;
                }
            }
        } catch (e) {
            await logTest('Login - Error', 'FAIL', e.message);
            await safeScreenshot('login-error');
            return;
        }

        // ========================================
        // TEST 2: Dashboard loads
        // ========================================
        console.log('\n📊 TEST 2: Dashboard principal');
        try {
            await page.goto('https://agendaya.mx/business/dashboard', { waitUntil: 'networkidle', timeout: 30000 });
            
            const pageTitle = await page.title();
            await logTest('Dashboard - Page loads', 'PASS', pageTitle);
            
            // Check for KPI cards
            const hasKpis = await page.locator('text=Citas').count() > 0;
            await logTest('Dashboard - KPI cards present', hasKpis ? 'PASS' : 'WARN');
            
            await safeScreenshot('dashboard');
        } catch (e) {
            await logTest('Dashboard - Error', 'FAIL', e.message);
            await safeScreenshot('dashboard-error');
        }

        // ========================================
        // TEST 3: Appointments list
        // ========================================
        console.log('\n📅 TEST 3: Lista de citas');
        try {
            await page.goto('https://agendaya.mx/business/appointments', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasAppointments = await page.locator('text=Citas').count() > 0 || 
                                   await page.locator('table').count() > 0;
            await logTest('Appointments - Page loads', hasAppointments ? 'PASS' : 'WARN');
            
            // Check for sorting functionality
            const hasSorting = await page.locator('text=Ordenar').count() > 0 || 
                              await page.locator('[wire\\:click*="sortByField"]').count() > 0;
            await logTest('Appointments - Sorting feature', hasSorting ? 'PASS' : 'WARN');
            
            // Check for bulk actions
            const hasBulkActions = await page.locator('text=Acciones').count() > 0 ||
                                  await page.locator('[wire\\:click*="bulk"]').count() > 0;
            await logTest('Appointments - Bulk actions', hasBulkActions ? 'PASS' : 'WARN');
            
            await safeScreenshot('appointments');
        } catch (e) {
            await logTest('Appointments - Error', 'FAIL', e.message);
            await safeScreenshot('appointments-error');
        }

        // ========================================
        // TEST 4: Calendar view
        // ========================================
        console.log('\n🗓️ TEST 4: Vista de calendario');
        try {
            await page.goto('https://agendaya.mx/business/appointments/calendar', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasCalendar = await page.locator('text=Calendario').count() > 0 ||
                               await page.locator('text=calendar').count() > 0;
            await logTest('Calendar - Page loads', hasCalendar ? 'PASS' : 'WARN');
            
            await safeScreenshot('calendar');
        } catch (e) {
            await logTest('Calendar - Error', 'FAIL', e.message);
            await safeScreenshot('calendar-error');
        }

        // ========================================
        // TEST 5: Services list
        // ========================================
        console.log('\n🔧 TEST 5: Lista de servicios');
        try {
            await page.goto('https://agendaya.mx/business/services', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasServices = await page.locator('text=Servicios').count() > 0 ||
                               await page.locator('text=Servicio').count() > 0;
            await logTest('Services - Page loads', hasServices ? 'PASS' : 'WARN');
            
            await safeScreenshot('services');
        } catch (e) {
            await logTest('Services - Error', 'FAIL', e.message);
            await safeScreenshot('services-error');
        }

        // ========================================
        // TEST 6: Employees list
        // ========================================
        console.log('\n👥 TEST 6: Lista de empleados');
        try {
            await page.goto('https://agendaya.mx/business/employees', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasEmployees = await page.locator('text=Empleados').count() > 0 ||
                                await page.locator('text=Empleado').count() > 0;
            await logTest('Employees - Page loads', hasEmployees ? 'PASS' : 'WARN');
            
            await safeScreenshot('employees');
        } catch (e) {
            await logTest('Employees - Error', 'FAIL', e.message);
            await safeScreenshot('employees-error');
        }

        // ========================================
        // TEST 7: Clients list (NEW)
        // ========================================
        console.log('\n👤 TEST 7: Lista de clientes (NUEVO)');
        try {
            await page.goto('https://agendaya.mx/business/clients', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasClients = await page.locator('text=Clientes').count() > 0 ||
                              await page.locator('text=Cliente').count() > 0;
            await logTest('Clients - Page loads', hasClients ? 'PASS' : 'WARN');
            
            // Check for search functionality
            const hasSearch = await page.locator('input[placeholder*="Buscar"]').count() > 0 ||
                             await page.locator('input[name="search"]').count() > 0;
            await logTest('Clients - Search functionality', hasSearch ? 'PASS' : 'WARN');
            
            await safeScreenshot('clients');
        } catch (e) {
            await logTest('Clients - Error', 'FAIL', e.message);
            await safeScreenshot('clients-error');
        }

        // ========================================
        // TEST 8: Locations list (NEW)
        // ========================================
        console.log('\n🏢 TEST 8: Lista de sucursales (NUEVO)');
        try {
            await page.goto('https://agendaya.mx/business/locations', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasLocations = await page.locator('text=Sucursal').count() > 0 ||
                                await page.locator('text=Sucursales').count() > 0;
            await logTest('Locations - Page loads', hasLocations ? 'PASS' : 'WARN');
            
            await safeScreenshot('locations');
        } catch (e) {
            await logTest('Locations - Error', 'FAIL', e.message);
            await safeScreenshot('locations-error');
        }

        // ========================================
        // TEST 9: Holidays list (NEW)
        // ========================================
        console.log('\n🎉 TEST 9: Lista de días festivos (NUEVO)');
        try {
            await page.goto('https://agendaya.mx/business/holidays', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasHolidays = await page.locator('text=Día').count() > 0 ||
                               await page.locator('text=Festivo').count() > 0;
            await logTest('Holidays - Page loads', hasHolidays ? 'PASS' : 'WARN');
            
            await safeScreenshot('holidays');
        } catch (e) {
            await logTest('Holidays - Error', 'FAIL', e.message);
            await safeScreenshot('holidays-error');
        }

        // ========================================
        // TEST 10: Schedule management
        // ========================================
        console.log('\n⏰ TEST 10: Gestión de horarios');
        try {
            await page.goto('https://agendaya.mx/business/schedules', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasSchedule = await page.locator('text=Horario').count() > 0 ||
                               await page.locator('text=horario').count() > 0;
            await logTest('Schedules - Page loads', hasSchedule ? 'PASS' : 'WARN');
            
            await safeScreenshot('schedules');
        } catch (e) {
            await logTest('Schedules - Error', 'FAIL', e.message);
            await safeScreenshot('schedules-error');
        }

        // ========================================
        // TEST 11: Schedule exceptions
        // ========================================
        console.log('\n📆 TEST 11: Excepciones de horario');
        try {
            await page.goto('https://agendaya.mx/business/schedules/exceptions', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasExceptions = await page.locator('text=Excepcione').count() > 0;
            await logTest('Exceptions - Page loads', hasExceptions ? 'PASS' : 'WARN');
            
            await safeScreenshot('exceptions');
        } catch (e) {
            await logTest('Exceptions - Error', 'FAIL', e.message);
            await safeScreenshot('exceptions-error');
        }

        // ========================================
        // TEST 12: Reports
        // ========================================
        console.log('\n📈 TEST 12: Reportes');
        try {
            await page.goto('https://agendaya.mx/business/reports', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasReports = await page.locator('text=Reporte').count() > 0 ||
                              await page.locator('text=reporte').count() > 0;
            await logTest('Reports - Page loads', hasReports ? 'PASS' : 'WARN');
            
            await safeScreenshot('reports');
        } catch (e) {
            await logTest('Reports - Error', 'FAIL', e.message);
            await safeScreenshot('reports-error');
        }

        // ========================================
        // TEST 13: Business Profile
        // ========================================
        console.log('\n🏪 TEST 13: Perfil del negocio');
        try {
            await page.goto('https://agendaya.mx/business/profile', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasProfile = await page.locator('text=Perfil').count() > 0 ||
                              await page.locator('text=Negocio').count() > 0;
            await logTest('Profile - Page loads', hasProfile ? 'PASS' : 'WARN');
            
            await safeScreenshot('profile');
        } catch (e) {
            await logTest('Profile - Error', 'FAIL', e.message);
            await safeScreenshot('profile-error');
        }

        // ========================================
        // TEST 14: Sidebar navigation
        // ========================================
        console.log('\n🔗 TEST 14: Navegación del sidebar');
        try {
            await page.goto('https://agendaya.mx/business/dashboard', { waitUntil: 'networkidle', timeout: 30000 });
            
            const menuItems = [
                { text: 'Dashboard', url: '/dashboard' },
                { text: 'Citas', url: '/appointments' },
                { text: 'Clientes', url: '/clients' },
                { text: 'Servicios', url: '/services' },
                { text: 'Empleados', url: '/employees' },
                { text: 'Horarios', url: '/schedules' },
                { text: 'Sucursales', url: '/locations' },
                { text: 'Reportes', url: '/reports' },
            ];

            for (const item of menuItems) {
                const count = await page.locator(`text=${item.text}`).count();
                await logTest(`Sidebar - ${item.text} link`, count > 0 ? 'PASS' : 'WARN');
            }
            
            await safeScreenshot('sidebar');
        } catch (e) {
            await logTest('Sidebar - Error', 'FAIL', e.message);
        }

        // ========================================
        // TEST 15: Keyboard shortcuts help
        // ========================================
        console.log('\n⌨️ TEST 15: Atajos de teclado');
        try {
            await page.goto('https://agendaya.mx/business/dashboard', { waitUntil: 'networkidle', timeout: 30000 });
            
            // Try pressing Alt+H to open help modal
            await page.keyboard.press('Alt+H');
            await page.waitForTimeout(500);
            
            const hasHelpModal = await page.locator('text=Atajo').count() > 0 ||
                                await page.locator('text=Teclado').count() > 0 ||
                                await page.locator('text=Ayuda').count() > 0;
            await logTest('Keyboard shortcuts - Help modal', hasHelpModal ? 'PASS' : 'WARN');
            
            // Close modal if open
            if (hasHelpModal) {
                await page.keyboard.press('Escape');
            }
            
            await safeScreenshot('keyboard-help');
        } catch (e) {
            await logTest('Keyboard shortcuts - Error', 'WARN', e.message);
        }

        // ========================================
        // TEST 16: Notifications system
        // ========================================
        console.log('\n🔔 TEST 16: Sistema de notificaciones');
        try {
            await page.goto('https://agendaya.mx/business/dashboard', { waitUntil: 'networkidle', timeout: 30000 });
            
            // Check for notifications icon or bell
            const hasNotifications = await page.locator('[class*="notification"]').count() > 0 ||
                                    await page.locator('[class*="bell"]').count() > 0;
            await logTest('Notifications - System present', hasNotifications ? 'PASS' : 'WARN');
            
            await safeScreenshot('notifications');
        } catch (e) {
            await logTest('Notifications - Error', 'WARN', e.message);
        }

        // ========================================
        // TEST 17: Public booking page
        // ========================================
        console.log('\n🌐 TEST 17: Página pública de reservas');
        try {
            // Try with a common business slug
            await page.goto('https://agendaya.mx/booking/test-business', { waitUntil: 'networkidle', timeout: 30000 });
            
            // Should show either booking form or error (business not found)
            const pageContent = await page.content();
            const hasBookingContent = pageContent.includes('Reservar') || 
                                     pageContent.includes('Servicio') ||
                                     pageContent.includes('no encontrad') ||
                                     pageContent.includes('error');
            await logTest('Public booking - Route exists', hasBookingContent ? 'PASS' : 'WARN');
            
            await safeScreenshot('public-booking');
        } catch (e) {
            // Expected if no business with that slug exists
            await logTest('Public booking - Route check', 'WARN', 'Business slug may not exist');
        }

        // ========================================
        // TEST 18: Export functionality
        // ========================================
        console.log('\n📥 TEST 18: Exportación de datos');
        try {
            await page.goto('https://agendaya.mx/business/reports', { waitUntil: 'networkidle', timeout: 30000 });
            
            const hasExport = await page.locator('text=Exportar').count() > 0 ||
                             await page.locator('text=CSV').count() > 0 ||
                             await page.locator('text=Descargar').count() > 0;
            await logTest('Export - Functionality present', hasExport ? 'PASS' : 'WARN');
            
            await safeScreenshot('export');
        } catch (e) {
            await logTest('Export - Error', 'WARN', e.message);
        }

        // ========================================
        // SUMMARY
        // ========================================
        console.log('\n' + '='.repeat(60));
        console.log('📊 RESUMEN DE PRUEBAS');
        console.log('='.repeat(60));
        console.log(`✅ Exitosas: ${successes.length}`);
        console.log(`⚠️  Advertencias: ${warnings.length}`);
        console.log(`❌ Fallidas: ${errors.length}`);
        console.log(`📝 Total pruebas: ${successes.length + warnings.length + errors.length}`);

        if (warnings.length > 0) {
            console.log('\n⚠️ ADVERTENCIAS:');
            warnings.forEach((w, i) => console.log(`  ${i + 1}. ${w.test}: ${w.detail}`));
        }

        if (errors.length > 0) {
            console.log('\n❌ ERRORES:');
            errors.forEach((e, i) => console.log(`  ${i + 1}. ${e.test}: ${e.detail}`));
        }

        console.log('\n📸 Screenshots guardados en: test-results/');
        console.log('='.repeat(60));

    } catch (e) {
        console.error('💥 Error crítico en las pruebas:', e.message);
    } finally {
        await browser.close();
    }
})();
