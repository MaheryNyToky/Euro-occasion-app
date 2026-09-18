import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';

class DashboardPage extends StatelessWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(30),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Tableau de bord', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w700, color: AppColors.text)),
        const SizedBox(height: 6),
        const Text('La situation de votre activité en un coup d’œil.', style: TextStyle(color: AppColors.muted)),
        const SizedBox(height: 28),
        LayoutBuilder(builder: (context, constraints) {
          final columns = constraints.maxWidth > 1000 ? 4 : constraints.maxWidth > 600 ? 2 : 1;
          return GridView.count(crossAxisCount: columns, crossAxisSpacing: 16, mainAxisSpacing: 16, childAspectRatio: 1.9, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), children: const [
            _MetricCard('Valeur du stock', '— MGA', 'En attente des données stock', Icons.inventory_2_outlined, AppColors.primary),
            _MetricCard('Ventes du mois', '— MGA', 'Aucune vente enregistrée', Icons.trending_up, Color(0xFF3B82F6)),
            _MetricCard('Produits actifs', '—', 'Catalogue à compléter', Icons.category_outlined, Color(0xFF8B5CF6)),
            _MetricCard('Alertes', '0', 'Aucune alerte critique', Icons.notifications_none, AppColors.warning),
          ]);
        }),
        const SizedBox(height: 24),
        LayoutBuilder(builder: (context, constraints) {
          if (constraints.maxWidth < 850) return const Column(children: [_RecentActivity(), SizedBox(height: 18), _QuickActions()]);
          return const Row(crossAxisAlignment: CrossAxisAlignment.start, children: [Expanded(child: _RecentActivity()), SizedBox(width: 18), SizedBox(width: 300, child: _QuickActions())]);
        }),
      ]),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard(this.label, this.value, this.caption, this.icon, this.color);

  final String label;
  final String value;
  final String caption;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(child: Padding(padding: const EdgeInsets.all(19), child: Row(children: [
      Container(width: 44, height: 44, decoration: BoxDecoration(color: color.withValues(alpha: .1), borderRadius: BorderRadius.circular(12)), child: Icon(icon, color: color)),
      const SizedBox(width: 13),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 12)), const SizedBox(height: 4), Text(value, style: const TextStyle(color: AppColors.text, fontWeight: FontWeight.w700, fontSize: 20)), const SizedBox(height: 3), Text(caption, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 11))])),
    ])));
  }
}

class _RecentActivity extends StatelessWidget {
  const _RecentActivity();

  @override
  Widget build(BuildContext context) {
    return Card(child: Padding(padding: const EdgeInsets.all(22), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text('Activité récente', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.text)),
      const SizedBox(height: 22),
      Container(padding: const EdgeInsets.all(18), decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(10)), child: const Row(children: [Icon(Icons.info_outline, color: AppColors.primary), SizedBox(width: 12), Expanded(child: Text('Les mouvements de stock, achats et ventes apparaîtront ici.', style: TextStyle(color: AppColors.muted)))])),
    ])));
  }
}

class _QuickActions extends StatelessWidget {
  const _QuickActions();

  @override
  Widget build(BuildContext context) {
    return Card(child: Padding(padding: const EdgeInsets.all(22), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text('Actions rapides', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.text)),
      const SizedBox(height: 16),
      _ActionButton(icon: Icons.add_box_outlined, label: 'Ajouter un produit', onTap: () {}),
      _ActionButton(icon: Icons.local_shipping_outlined, label: 'Créer une commande', onTap: () {}),
      _ActionButton(icon: Icons.qr_code_scanner, label: 'Scanner un produit', onTap: () {}),
    ])));
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: OutlinedButton.icon(
        onPressed: onTap,
        icon: Icon(icon, size: 19),
        label: Align(alignment: Alignment.centerLeft, child: Text(label)),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(44),
          alignment: Alignment.centerLeft,
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.border),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
        ),
      ),
    );
  }
}
