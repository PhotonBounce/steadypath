package com.photonbounce.leads.fragments

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import androidx.appcompat.widget.SearchView
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.ItemTouchHelper
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.activities.LeadDetailActivity
import com.photonbounce.leads.activities.MainActivity
import com.photonbounce.leads.adapters.LeadsAdapter
import com.photonbounce.leads.databinding.FragmentLeadsBinding
import com.photonbounce.leads.viewmodels.LeadsViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory
import com.google.android.material.snackbar.Snackbar

class LeadsFragment : Fragment() {

    private var _binding: FragmentLeadsBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: LeadsViewModel
    private lateinit var adapter: LeadsAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentLeadsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[LeadsViewModel::class.java]

        adapter = LeadsAdapter(
            onClick = { leadId ->
                (requireActivity() as MainActivity).showInterstitial()
                val intent = Intent(requireContext(), LeadDetailActivity::class.java)
                intent.putExtra(LeadDetailActivity.EXTRA_LEAD_ID, leadId)
                startActivity(intent)
            },
            onStatusChange = { id, status ->
                viewModel.updateLeadStatus(id, status)
            }
        )

        binding.recyclerLeads.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerLeads.adapter = adapter

        // Swipe actions
        val swipeHandler = object : ItemTouchHelper.SimpleCallback(0, ItemTouchHelper.LEFT or ItemTouchHelper.RIGHT) {
            override fun onMove(
                recyclerView: RecyclerView,
                viewHolder: RecyclerView.ViewHolder,
                target: RecyclerView.ViewHolder
            ): Boolean = false

            override fun onSwiped(viewHolder: RecyclerView.ViewHolder, direction: Int) {
                val position = viewHolder.adapterPosition
                val lead = adapter.currentList[position]
                when (direction) {
                    ItemTouchHelper.LEFT -> {
                        viewModel.updateLeadStatus(lead.id, "archived")
                        Snackbar.make(binding.root, "Lead archived", Snackbar.LENGTH_SHORT).show()
                    }
                    ItemTouchHelper.RIGHT -> {
                        // Open detail
                        val intent = Intent(requireContext(), LeadDetailActivity::class.java)
                        intent.putExtra(LeadDetailActivity.EXTRA_LEAD_ID, lead.id)
                        startActivity(intent)
                        adapter.notifyItemChanged(position)
                    }
                }
            }
        }
        ItemTouchHelper(swipeHandler).attachToRecyclerView(binding.recyclerLeads)

        // Search
        binding.searchView.setOnQueryTextListener(object : SearchView.OnQueryTextListener {
            override fun onQueryTextSubmit(query: String?): Boolean = false
            override fun onQueryTextChange(newText: String?): Boolean {
                viewModel.search(newText ?: "")
                return true
            }
        })

        // Status filter
        val statuses = listOf("All", "New", "Contacted", "Qualified", "Proposal", "Won", "Lost", "Archived")
        binding.spinnerFilter.adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_spinner_dropdown_item,
            statuses
        )
        binding.spinnerFilter.onItemSelectedListener = object : android.widget.AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: android.widget.AdapterView<*>?, view: android.view.View?, position: Int, id: Long) {
                val status = statuses[position].lowercase().takeIf { it != "all" }
                viewModel.filterByStatus(status)
            }
            override fun onNothingSelected(parent: android.widget.AdapterView<*>?) {}
        }

        binding.swipeRefresh.setOnRefreshListener {
            viewModel.refresh()
        }

        viewModel.leads.observe(viewLifecycleOwner) { leads ->
            adapter.submitList(leads)
            binding.emptyState.visibility = if (leads.isEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.isRefreshing.observe(viewLifecycleOwner) {
            binding.swipeRefresh.isRefreshing = it
        }

        viewModel.refresh()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
