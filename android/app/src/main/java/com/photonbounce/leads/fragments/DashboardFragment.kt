package com.photonbounce.leads.fragments

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.LinearLayoutManager
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.activities.LeadDetailActivity
import com.photonbounce.leads.adapters.RecentLeadsAdapter
import com.photonbounce.leads.databinding.FragmentDashboardBinding
import com.photonbounce.leads.viewmodels.DashboardViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory

class DashboardFragment : Fragment() {

    private var _binding: FragmentDashboardBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: DashboardViewModel
    private lateinit var adapter: RecentLeadsAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentDashboardBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[DashboardViewModel::class.java]

        adapter = RecentLeadsAdapter { leadId ->
            val intent = Intent(requireContext(), LeadDetailActivity::class.java)
            intent.putExtra(LeadDetailActivity.EXTRA_LEAD_ID, leadId)
            startActivity(intent)
        }

        binding.recyclerRecentLeads.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerRecentLeads.adapter = adapter

        binding.swipeRefresh.setOnRefreshListener {
            viewModel.refresh()
        }

        viewModel.stats.observe(viewLifecycleOwner) { stats ->
            binding.tvTotalLeads.text = stats.totalLeads.toString()
            binding.tvWonLeads.text = stats.wonLeads.toString()
            binding.tvNiches.text = stats.activeNiches.toString()

            if (viewModel.isVip) {
                binding.tvMlScore.text = stats.avgMlScore?.let { "${(it * 100).toInt()}%" } ?: "--"
                binding.tvMlScoreLabel.visibility = View.VISIBLE
            } else {
                binding.tvMlScore.text = "★"
                binding.tvMlScoreLabel.visibility = View.GONE
            }
        }

        viewModel.recentLeads.observe(viewLifecycleOwner) { leads ->
            adapter.submitList(leads)
            binding.emptyState.visibility = if (leads.isEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.isRefreshing.observe(viewLifecycleOwner) {
            binding.swipeRefresh.isRefreshing = it
        }

        binding.btnViewAll.setOnClickListener {
            // Navigate to leads tab
            (requireActivity() as com.photonbounce.leads.activities.MainActivity)
                .findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(
                    com.photonbounce.leads.R.id.bottom_nav
                ).selectedItemId = com.photonbounce.leads.R.id.nav_leads
        }

        viewModel.refresh()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
