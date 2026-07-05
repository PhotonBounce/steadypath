package com.photonbounce.leads.fragments

import android.app.AlertDialog
import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.activities.AuthActivity
import com.photonbounce.leads.databinding.FragmentSettingsBinding
import com.photonbounce.leads.viewmodels.SettingsViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory
import com.google.android.material.snackbar.Snackbar

class SettingsFragment : Fragment() {

    private var _binding: FragmentSettingsBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: SettingsViewModel

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentSettingsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[SettingsViewModel::class.java]

        // Timezone spinner
        val timezones = java.util.TimeZone.getAvailableIDs().sorted()
        binding.spinnerTimezone.adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_spinner_dropdown_item,
            timezones
        )

        viewModel.user.observe(viewLifecycleOwner) { user ->
            binding.etName.setText(user.name)
            val tzIndex = timezones.indexOf(user.timezone)
            if (tzIndex >= 0) binding.spinnerTimezone.setSelection(tzIndex)
        }

        viewModel.tier.observe(viewLifecycleOwner) { tier ->
            binding.tvTier.text = if (tier.tier == "vip") "VIP Plan" else "Free Plan"
            if (tier.tier == "vip") {
                binding.btnUpgrade.visibility = View.GONE
                binding.tvTier.setTextColor(android.graphics.Color.parseColor("#FBBF24"))
            } else {
                binding.btnUpgrade.visibility = View.VISIBLE
                binding.tvTier.setTextColor(android.graphics.Color.parseColor("#9CA3AF"))
            }
        }

        viewModel.notificationsEnabled.observe(viewLifecycleOwner) {
            binding.switchNotifications.isChecked = it
        }

        binding.switchNotifications.setOnCheckedChangeListener { _, checked ->
            viewModel.setNotificationsEnabled(checked)
        }

        binding.btnSave.setOnClickListener {
            val name = binding.etName.text.toString().trim()
            val timezone = binding.spinnerTimezone.selectedItem?.toString() ?: "UTC"
            viewModel.updateProfile(name, timezone)
        }

        binding.btnUpgrade.setOnClickListener {
            // Launch billing flow
            showUpgradeDialog()
        }

        binding.btnLogout.setOnClickListener {
            AlertDialog.Builder(requireContext())
                .setTitle("Log Out")
                .setMessage("Are you sure you want to log out?")
                .setPositiveButton("Log Out") { _, _ ->
                    viewModel.logout()
                    startActivity(Intent(requireContext(), AuthActivity::class.java))
                    requireActivity().finish()
                }
                .setNegativeButton("Cancel", null)
                .show()
        }

        viewModel.updateResult.observe(viewLifecycleOwner) { success ->
            if (success) {
                Snackbar.make(binding.root, "Profile updated", Snackbar.LENGTH_SHORT).show()
            }
        }

        viewModel.loadUserData()
    }

    private fun showUpgradeDialog() {
        AlertDialog.Builder(requireContext())
            .setTitle("Upgrade to VIP")
            .setMessage("Choose your plan:\n\n$19/month\n$199/year (Save 13%)")
            .setPositiveButton("Monthly") { _, _ ->
                // Launch monthly billing
            }
            .setNeutralButton("Yearly") { _, _ ->
                // Launch yearly billing
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
