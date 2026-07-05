package com.photonbounce.leads.fragments

import android.app.AlertDialog
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.EditText
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.GridLayoutManager
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.adapters.MicrositesAdapter
import com.photonbounce.leads.databinding.FragmentMicrositesBinding
import com.photonbounce.leads.viewmodels.MicrositesViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory
import com.google.android.material.snackbar.Snackbar

class MicrositesFragment : Fragment() {

    private var _binding: FragmentMicrositesBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: MicrositesViewModel
    private lateinit var adapter: MicrositesAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentMicrositesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[MicrositesViewModel::class.java]

        adapter = MicrositesAdapter(
            onToggle = { id, active -> viewModel.toggleMicrosite(id, active) }
        )

        binding.recyclerMicrosites.layoutManager = GridLayoutManager(requireContext(), 2)
        binding.recyclerMicrosites.adapter = adapter

        binding.swipeRefresh.setOnRefreshListener {
            viewModel.refresh()
        }

        binding.fabAdd.setOnClickListener {
            if (!viewModel.isVip && (viewModel.microsites.value?.size ?: 0) >= 1) {
                Snackbar.make(binding.root, "Free tier: 1 microsite max. Upgrade to VIP!", Snackbar.LENGTH_LONG).show()
                return@setOnClickListener
            }
            showAddMicrositeDialog()
        }

        viewModel.microsites.observe(viewLifecycleOwner) { microsites ->
            adapter.submitList(microsites)
            binding.emptyState.visibility = if (microsites.isEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.isRefreshing.observe(viewLifecycleOwner) {
            binding.swipeRefresh.isRefreshing = it
        }

        viewModel.error.observe(viewLifecycleOwner) { error ->
            error?.let { Snackbar.make(binding.root, it, Snackbar.LENGTH_LONG).show() }
        }

        viewModel.refresh()
    }

    private fun showAddMicrositeDialog() {
        val dialogView = LayoutInflater.from(requireContext()).inflate(
            com.photonbounce.leads.R.layout.dialog_add_microsite, null
        )
        val etName = dialogView.findViewById<EditText>(com.photonbounce.leads.R.id.et_microsite_name)
        val etSlug = dialogView.findViewById<EditText>(com.photonbounce.leads.R.id.et_microsite_slug)
        val etNiche = dialogView.findViewById<EditText>(com.photonbounce.leads.R.id.et_microsite_niche)

        AlertDialog.Builder(requireContext())
            .setTitle("Add Microsite")
            .setView(dialogView)
            .setPositiveButton("Create") { _, _ ->
                val name = etName.text.toString().trim()
                val slug = etSlug.text.toString().trim()
                val niche = etNiche.text.toString().trim().takeIf { it.isNotEmpty() }
                if (name.isNotEmpty() && slug.isNotEmpty()) {
                    viewModel.createMicrosite(name, slug, niche)
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
