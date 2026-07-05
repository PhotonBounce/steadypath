package com.photonbounce.leads.adapters

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.photonbounce.leads.databinding.ItemLeadBinding
import com.photonbounce.leads.db.LeadEntity

class LeadsAdapter(
    private val onClick: (String) -> Unit,
    private val onStatusChange: (String, String) -> Unit
) : ListAdapter<LeadEntity, LeadsAdapter.LeadViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): LeadViewHolder {
        val binding = ItemLeadBinding.inflate(
            LayoutInflater.from(parent.context), parent, false
        )
        return LeadViewHolder(binding)
    }

    override fun onBindViewHolder(holder: LeadViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class LeadViewHolder(private val binding: ItemLeadBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(lead: LeadEntity) {
            binding.tvName.text = lead.name
            binding.tvEmail.text = lead.email ?: "No email"
            binding.tvStatus.text = lead.status.replaceFirstChar { it.uppercase() }
            binding.tvCompany.text = lead.company ?: ""

            // Status color
            val color = when (lead.status) {
                "new" -> android.graphics.Color.parseColor("#3B82F6")
                "contacted" -> android.graphics.Color.parseColor("#F59E0B")
                "qualified" -> android.graphics.Color.parseColor("#8B5CF6")
                "proposal" -> android.graphics.Color.parseColor("#EC4899")
                "won" -> android.graphics.Color.parseColor("#10B981")
                "lost" -> android.graphics.Color.parseColor("#EF4444")
                else -> android.graphics.Color.parseColor("#6B7280")
            }
            binding.tvStatus.setTextColor(color)

            binding.root.setOnClickListener { onClick(lead.id) }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<LeadEntity>() {
        override fun areItemsTheSame(old: LeadEntity, new: LeadEntity) = old.id == new.id
        override fun areContentsTheSame(old: LeadEntity, new: LeadEntity) = old == new
    }
}
