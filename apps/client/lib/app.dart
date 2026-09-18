import 'package:flutter/material.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/auth/services/auth_service.dart';
import 'features/catalog/presentation/catalog_page.dart';
import 'features/dashboard/presentation/dashboard_page.dart';
import 'features/stock/presentation/stock_page.dart';

class EurocasionApp extends StatefulWidget {
  const EurocasionApp({super.key});

  @override
  State<EurocasionApp> createState() => _EurocasionAppState();
}

class _EurocasionAppState extends State<EurocasionApp> {
  @override
  Widget build(BuildContext context) {
    final isAuthenticated = AuthService.instance.isAuthenticated;

    return MaterialApp(
      title: 'Eurocasion',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      home: isAuthenticated
          ? AppShell(
              onLogout: () => setState(() {}),
            )
          : LoginScreen(
              onLoginSuccess: () => setState(() {}),
            ),
    );
  }
}

class AppShell extends StatefulWidget {
  final VoidCallback? onLogout;

  const AppShell({super.key, this.onLogout});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _selectedIndex = 0;

  static const _items = [
    _NavigationItem('Tableau de bord', Icons.dashboard_outlined),
    _NavigationItem('Catalogue', Icons.inventory_2_outlined),
    _NavigationItem('Stock', Icons.warehouse_outlined),
    _NavigationItem('Achats', Icons.local_shipping_outlined),
    _NavigationItem('Ventes', Icons.point_of_sale_outlined),
    _NavigationItem('Rapports', Icons.bar_chart_outlined),
  ];

  @override
  Widget build(BuildContext context) {
    final compact = MediaQuery.sizeOf(context).width < 900;
    final session = AuthService.instance.currentSession;
    final page = switch (_selectedIndex) {
      0 => const DashboardPage(),
      1 => const CatalogPage(),
      2 => const StockPage(),
      _ => const _ComingSoonPage(),
    };

    return Scaffold(
      appBar: compact
          ? AppBar(
              title: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Eurocasion', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  if (session != null)
                    Text(session.tenant.name, style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                ],
              ),
              actions: [
                IconButton(
                  tooltip: 'Se déconnecter',
                  icon: const Icon(Icons.logout),
                  onPressed: () async {
                    await AuthService.instance.logout();
                    widget.onLogout?.call();
                  },
                ),
              ],
            )
          : null,
      drawer: compact
          ? Drawer(
              child: SafeArea(
                child: _Sidebar(
                  items: _items,
                  selectedIndex: _selectedIndex,
                  onSelected: (index) {
                    setState(() => _selectedIndex = index);
                    Navigator.pop(context);
                  },
                  onLogout: widget.onLogout,
                ),
              ),
            )
          : null,
      body: Row(
        children: [
          if (!compact)
            _Sidebar(
              items: _items,
              selectedIndex: _selectedIndex,
              onSelected: (index) => setState(() => _selectedIndex = index),
              onLogout: widget.onLogout,
            ),
          Expanded(
            child: Column(
              children: [
                if (!compact) _DesktopHeader(onLogout: widget.onLogout),
                Expanded(child: page),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Sidebar extends StatelessWidget {
  const _Sidebar({
    required this.items,
    required this.selectedIndex,
    required this.onSelected,
    this.onLogout,
  });

  final List<_NavigationItem> items;
  final int selectedIndex;
  final ValueChanged<int> onSelected;
  final VoidCallback? onLogout;

  @override
  Widget build(BuildContext context) {
    final session = AuthService.instance.currentSession;

    return Material(
      color: AppColors.sidebar,
      child: SizedBox(
        width: 248,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 26, 20, 34),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: const Icon(Icons.all_inclusive, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Eurocasion',
                          style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        if (session != null)
                          Text(
                            session.tenant.name,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Color(0xFF8FA2BA), fontSize: 11),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 24),
              child: Text(
                'ESPACE DE TRAVAIL',
                style: TextStyle(color: Color(0xFF8FA2BA), fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 1.2),
              ),
            ),
            const SizedBox(height: 10),
            ...List.generate(items.length, (index) {
              final item = items[index];
              final selected = selectedIndex == index;
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
                child: ListTile(
                  selected: selected,
                  selectedTileColor: AppColors.sidebarSelected,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  leading: Icon(item.icon, color: selected ? Colors.white : const Color(0xFF9AAAC0), size: 21),
                  title: Text(
                    item.label,
                    style: TextStyle(
                      color: selected ? Colors.white : const Color(0xFFB7C4D5),
                      fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
                    ),
                  ),
                  onTap: () => onSelected(index),
                ),
              );
            }),
            const Spacer(),
            const Divider(color: Color(0xFF2A3A50), indent: 24, endIndent: 24),
            if (session != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 14,
                      backgroundColor: AppColors.primary,
                      child: Text(
                        session.user.name.isNotEmpty ? session.user.name[0].toUpperCase() : 'U',
                        style: const TextStyle(fontSize: 10, color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        session.user.name,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Color(0xFFB7C4D5), fontSize: 12, fontWeight: FontWeight.w500),
                      ),
                    ),
                  ],
                ),
              ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.logout, color: Color(0xFFE57373), size: 20),
              title: const Text('Déconnexion', style: TextStyle(color: Color(0xFFE57373), fontSize: 13)),
              onTap: () async {
                await AuthService.instance.logout();
                onLogout?.call();
              },
            ),
            const Padding(
              padding: EdgeInsets.fromLTRB(24, 4, 24, 20),
              child: Text(
                'Aperçu de l’interface\nVersion 1.0.0',
                style: TextStyle(color: Color(0xFF71839A), fontSize: 11, height: 1.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DesktopHeader extends StatelessWidget {
  final VoidCallback? onLogout;

  const _DesktopHeader({this.onLogout});

  @override
  Widget build(BuildContext context) {
    final session = AuthService.instance.currentSession;
    final userName = session?.user.name ?? 'Admin';
    final tenantName = session?.tenant.name ?? 'Eurocasion';
    final initials = userName.trim().split(' ').take(2).map((part) => part.isNotEmpty ? part[0] : '').join().toUpperCase();

    return Container(
      height: 76,
      padding: const EdgeInsets.symmetric(horizontal: 30),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  'Bonjour, $userName',
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                ),
                Text(
                  tenantName,
                  style: const TextStyle(fontSize: 12, color: AppColors.muted),
                ),
              ],
            ),
          ),
          const SizedBox(
            width: 260,
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Rechercher...',
                prefixIcon: Icon(Icons.search, size: 20),
                filled: true,
                fillColor: AppColors.background,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.all(Radius.circular(10)),
                  borderSide: BorderSide.none,
                ),
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
          const SizedBox(width: 18),
          IconButton(onPressed: () {}, icon: const Icon(Icons.notifications_none)),
          const SizedBox(width: 12),
          PopupMenuButton<String>(
            tooltip: 'Menu utilisateur',
            offset: const Offset(0, 48),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 17,
                  backgroundColor: AppColors.primary,
                  child: Text(
                    initials.isEmpty ? 'U' : initials,
                    style: const TextStyle(fontSize: 11, color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 9),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(userName, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    Text(session?.user.email ?? 'Administrateur', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                  ],
                ),
                const SizedBox(width: 4),
                const Icon(Icons.arrow_drop_down, color: AppColors.muted, size: 20),
              ],
            ),
            onSelected: (val) async {
              if (val == 'logout') {
                await AuthService.instance.logout();
                onLogout?.call();
              }
            },
            itemBuilder: (ctx) => [
              PopupMenuItem(
                enabled: false,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(userName, style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.text)),
                    Text(session?.user.email ?? '', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                    Text('Tenant: $tenantName', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                  ],
                ),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, size: 18, color: Colors.red),
                    SizedBox(width: 8),
                    Text('Se déconnecter', style: TextStyle(color: Colors.red)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ComingSoonPage extends StatelessWidget {
  const _ComingSoonPage();

  @override
  Widget build(BuildContext context) => const Center(
        child: Text('Ce module sera disponible dans une prochaine phase.', style: TextStyle(color: AppColors.muted)),
      );
}

class _NavigationItem {
  const _NavigationItem(this.label, this.icon);

  final String label;
  final IconData icon;
}
